<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response as SlimResponse;

// ══════════════════════════════════════════════════════════════════════════════
//  RATE LIMITER — file-based sliding-window (zero extra dependencies)
//
//  Stores per-IP request timestamps in the OS temp folder.
//  Default: 60 requests / 60 seconds per IP.
//  On failure (e.g. unwritable tmp dir) it fails OPEN so the API stays up.
// ══════════════════════════════════════════════════════════════════════════════

class RateLimiter
{
    private string $dir;
    private int    $limit;
    private int    $window;  // seconds

    public function __construct(int $limit = 60, int $windowSec = 60)
    {
        $this->limit  = $limit;
        $this->window = $windowSec;
        $this->dir    = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
                      . DIRECTORY_SEPARATOR . 'cookbook_rl' . DIRECTORY_SEPARATOR;

        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0700, true);
        }
    }

    /**
     * Record a hit for $ip and return rate-limit state.
     *
     * @return array{allowed:bool, remaining:int, limit:int, retry_after:int}
     */
    public function hit(string $ip): array
    {
        $file = $this->dir . md5($ip) . '.json';
        $lock = @fopen($file . '.lck', 'c');    // exclusive lock to avoid races
        if ($lock) {
            flock($lock, LOCK_EX);
        }

        $now    = microtime(true);
        $cutoff = $now - $this->window;

        // Load and prune timestamps outside the current window
        $hits = [];
        if (file_exists($file)) {
            $raw = @json_decode(@file_get_contents($file), true);
            if (is_array($raw)) {
                $hits = array_values(array_filter($raw, fn($t) => $t > $cutoff));
            }
        }

        $count = count($hits);

        if ($count >= $this->limit) {
            // Still blocked — release without writing
            if ($lock) { flock($lock, LOCK_UN); fclose($lock); }

            $oldest     = count($hits) ? min($hits) : $now;
            $retryAfter = (int) ceil($oldest + $this->window - $now);
            return [
                'allowed'     => false,
                'remaining'   => 0,
                'limit'       => $this->limit,
                'retry_after' => max(1, $retryAfter),
            ];
        }

        // Allow: record hit and persist
        $hits[] = $now;
        @file_put_contents($file, json_encode(array_values($hits)));

        if ($lock) { flock($lock, LOCK_UN); fclose($lock); }

        // Occasional GC: 1% chance, clean files older than 2× the window
        if (mt_rand(1, 100) === 1) {
            $this->gc();
        }

        return [
            'allowed'     => true,
            'remaining'   => $this->limit - count($hits),
            'limit'       => $this->limit,
            'retry_after' => 0,
        ];
    }

    /** Remove stale per-IP files from the temp directory. */
    private function gc(): void
    {
        $threshold = time() - ($this->window * 2);
        foreach (glob($this->dir . '*.json') ?: [] as $f) {
            if (@filemtime($f) < $threshold) {
                @unlink($f);
                @unlink($f . '.lck');
            }
        }
    }
}

// ══════════════════════════════════════════════════════════════════════════════
//  DATABASE CONNECTION
// ══════════════════════════════════════════════════════════════════════════════

function getDbConnection(): PDO
{
    $host    = '127.0.0.1';
    $db      = 'filipino_cookbook_api';
    $user    = 'root';
    $pass    = 'Xky101';          // adjust if your MySQL root has a password
    $charset = 'utf8mb4';

    return new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

// ══════════════════════════════════════════════════════════════════════════════
//  APP SETUP
// ══════════════════════════════════════════════════════════════════════════════

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

const API_TOKEN = 'dmmmsu-cookbook-token-2026';

// ══════════════════════════════════════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════════════════════════════════════

function jsonResponse(Response $response, $data, int $status = 200): Response
{
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}

function attachIngredients(PDO $pdo, array $food): array
{
    $stmt = $pdo->prepare("
        SELECT i.ingredient_name
        FROM food_ingredients fi
        JOIN ingredients i ON fi.ingredient_id = i.ingredient_id
        WHERE fi.food_id = ?
        ORDER BY i.ingredient_name
    ");
    $stmt->execute([$food['food_id']]);
    $food['ingredients'] = array_column($stmt->fetchAll(), 'ingredient_name');
    return $food;
}

// ══════════════════════════════════════════════════════════════════════════════
//  MIDDLEWARE DEFINITIONS
// ══════════════════════════════════════════════════════════════════════════════

// ── CORS ─────────────────────────────────────────────────────────────────────
//  Handles preflight OPTIONS immediately; adds CORS headers to every response.
// ─────────────────────────────────────────────────────────────────────────────
$corsMiddleware = function (Request $request, RequestHandler $handler): Response {
    if ($request->getMethod() === 'OPTIONS') {
        return (new SlimResponse())
            ->withHeader('Access-Control-Allow-Origin',  '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Max-Age',       '86400')
            ->withStatus(200);
    }

    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin',  '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
};

// ── RATE LIMITER ──────────────────────────────────────────────────────────────
//  Applied to /api/* only. Returns 429 when the IP hits the limit.
//  Adds X-RateLimit-Limit / X-RateLimit-Remaining to every API response.
//  Added by (editor: Stradlin)
// ─────────────────────────────────────────────────────────────────────────────
$rateLimitMiddleware = function (Request $request, RequestHandler $handler): Response {
    $params = $request->getServerParams();

    // Resolve real client IP (proxy-aware)
    $ip = $params['HTTP_X_FORWARDED_FOR']
        ?? $params['HTTP_CF_CONNECTING_IP']
        ?? $params['REMOTE_ADDR']
        ?? '0.0.0.0';
    $ip = trim(explode(',', $ip)[0]);   // first IP if comma-list

    $limiter = new RateLimiter(60, 60); // 60 req / 60 s
    $result  = $limiter->hit($ip);

    if (!$result['allowed']) {
        $res = new SlimResponse();
        $res->getBody()->write(json_encode([
            'status'      => 'error',
            'message'     => 'Rate limit exceeded. Too many requests — please slow down.',
            'retry_after' => $result['retry_after'],
        ], JSON_PRETTY_PRINT));
        return $res
            ->withHeader('Content-Type',          'application/json')
            ->withHeader('X-RateLimit-Limit',     (string) $result['limit'])
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('Retry-After',           (string) $result['retry_after'])
            ->withStatus(429);
    }

    $response = $handler->handle($request);
    return $response
        ->withHeader('X-RateLimit-Limit',     (string) $result['limit'])
        ->withHeader('X-RateLimit-Remaining', (string) $result['remaining']);
};

// ── TOKEN AUTH ────────────────────────────────────────────────────────────────
//  Applied to /api/* only, AFTER rate limiting.
// ─────────────────────────────────────────────────────────────────────────────
$authMiddleware = function (Request $request, RequestHandler $handler): Response {
    $authHeader = $request->getHeaderLine('Authorization');

    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)
        || $matches[1] !== API_TOKEN
    ) {
        return jsonResponse(new SlimResponse(), [
            'status'  => 'error',
            'message' => 'Unauthorized. A valid Bearer token is required.',
        ], 401);
    }

    return $handler->handle($request);
};

// ══════════════════════════════════════════════════════════════════════════════
//  GLOBAL MIDDLEWARE  (CORS wraps every route)
// ══════════════════════════════════════════════════════════════════════════════

$app->add($corsMiddleware);

// ══════════════════════════════════════════════════════════════════════════════
//  UI ROUTES  (no auth, no rate-limit — serve the HTML frontend)
// ══════════════════════════════════════════════════════════════════════════════

/** Access-key portal */
$app->get('/', function (Request $request, Response $response) {
    $file = __DIR__ . '/index.html';
    if (!file_exists($file)) {
        return jsonResponse($response, ['error' => 'UI not found'], 404);
    }
    $response->getBody()->write(file_get_contents($file));
    return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
});

/** Main cookbook application */
$app->get('/app', function (Request $request, Response $response) {
    $file = __DIR__ . '/app.html';
    if (!file_exists($file)) {
        return jsonResponse($response, ['error' => 'App not found'], 404);
    }
    $response->getBody()->write(file_get_contents($file));
    return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
});

// ══════════════════════════════════════════════════════════════════════════════
//  PUBLIC API STATUS ROUTE  (no auth — used by the frontend health ping)
// ══════════════════════════════════════════════════════════════════════════════

$app->get('/api/status', function (Request $request, Response $response) {
    return jsonResponse($response, [
        'status'  => 'online',
        'message' => 'Filipino Cookbook API is running.',
    ]);
});

// ══════════════════════════════════════════════════════════════════════════════
//  SECURED API ROUTES  (rate-limited → then auth-gated)
//
//  Middleware runs LIFO on requests, so:
//    add($authMiddleware)       ← inner, runs 2nd
//    add($rateLimitMiddleware)  ← outer, runs 1st
//
//  Request path:  rateLimitMiddleware → authMiddleware → route handler
//  Response path: route handler → authMiddleware → rateLimitMiddleware
// ══════════════════════════════════════════════════════════════════════════════

$app->group('/api', function ($group) {

    /* ── GET /api/foods ── */
    $group->get('/foods', function (Request $request, Response $response) {
        $pdo   = getDbConnection();
        $stmt  = $pdo->query("
            SELECT f.food_id, f.food_name, c.category_name, o.origin_name, f.instructions
            FROM foods f
            JOIN categories c ON f.category_id = c.category_id
            JOIN origins    o ON f.origin_id   = o.origin_id
            ORDER BY f.food_id
        ");
        $foods = array_map(fn($f) => attachIngredients($pdo, $f), $stmt->fetchAll());
        return jsonResponse($response, $foods);
    });

    /* ── GET /api/foods/{id} ── */
    $group->get('/foods/{id}', function (Request $request, Response $response, array $args) {
        $pdo  = getDbConnection();
        $id   = (int) $args['id'];
        $stmt = $pdo->prepare("
            SELECT f.food_id, f.food_name, c.category_name, o.origin_name, f.instructions
            FROM foods f
            JOIN categories c ON f.category_id = c.category_id
            JOIN origins    o ON f.origin_id   = o.origin_id
            WHERE f.food_id = ?
        ");
        $stmt->execute([$id]);
        $food = $stmt->fetch();

        if (!$food) {
            return jsonResponse($response, ['status' => 'error', 'message' => 'Food not found'], 404);
        }
        return jsonResponse($response, attachIngredients($pdo, $food));
    });

    /* ── GET /api/foods/search/{name} ── */
    $group->get('/foods/search/{name}', function (Request $request, Response $response, array $args) {
        $pdo  = getDbConnection();
        $name = $args['name'];
        $stmt = $pdo->prepare("
            SELECT f.food_id, f.food_name, c.category_name, o.origin_name, f.instructions
            FROM foods f
            JOIN categories c ON f.category_id = c.category_id
            JOIN origins    o ON f.origin_id   = o.origin_id
            WHERE f.food_name LIKE ?
            ORDER BY f.food_name
        ");
        $stmt->execute(["%{$name}%"]);
        $foods = $stmt->fetchAll();

        if (!$foods) {
            return jsonResponse($response, ['status' => 'error', 'message' => 'No matching food found'], 404);
        }
        return jsonResponse($response, array_map(fn($f) => attachIngredients($pdo, $f), $foods));
    });

    /* ── GET /api/categories ── */
    $group->get('/categories', function (Request $request, Response $response) {
        $pdo  = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY category_id");
        return jsonResponse($response, $stmt->fetchAll());
    });

    /* ── GET /api/ingredients ── */
    $group->get('/ingredients', function (Request $request, Response $response) {
        $pdo  = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM ingredients ORDER BY ingredient_id");
        return jsonResponse($response, $stmt->fetchAll());
    });

    /* ── POST /api/foods ── */
    $group->post('/foods', function (Request $request, Response $response) {
        $data     = $request->getParsedBody();
        $required = ['food_name', 'category_id', 'origin_id', 'instructions', 'ingredient_ids'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return jsonResponse($response, [
                    'status'  => 'error',
                    'message' => "Missing required field: {$field}",
                ], 400);
            }
        }

        $pdo = getDbConnection();
        try {
            $pdo->beginTransaction();

            // food_id is NOT AUTO_INCREMENT — calculate next ID manually
            $nextId = (int) $pdo->query(
                "SELECT COALESCE(MAX(food_id), 0) + 1 AS nid FROM foods"
            )->fetch()['nid'];

            $pdo->prepare("
                INSERT INTO foods (food_id, food_name, category_id, origin_id, instructions)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $nextId,
                $data['food_name'],
                $data['category_id'],
                $data['origin_id'],
                $data['instructions'],
            ]);

            $ins = $pdo->prepare(
                "INSERT INTO food_ingredients (food_id, ingredient_id) VALUES (?, ?)"
            );
            foreach ((array) $data['ingredient_ids'] as $iid) {
                $ins->execute([$nextId, (int) $iid]);
            }

            $pdo->commit();
            return jsonResponse($response, [
                'status'  => 'success',
                'message' => 'Food added successfully.',
                'food_id' => $nextId,
            ], 201);
        } catch (PDOException $e) {
            $pdo->rollBack();
            return jsonResponse($response, [
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);
        }
    });


/* ── PUT /api/foods/{id} ── */
$group->put('/foods/{id}', function (Request $request, Response $response, array $args) {
$pdo=getDbConnection();$id=(int)$args['id'];$data=$request->getParsedBody();
$s=$pdo->prepare("SELECT * FROM foods WHERE food_id=?");$s->execute([$id]);if(!$s->fetch()) return jsonResponse($response,["status"=>"error","message"=>"Food not found"],404);
try{$pdo->beginTransaction();$pdo->prepare("UPDATE foods SET food_name=?,category_id=?,origin_id=?,instructions=? WHERE food_id=?")->execute([$data["food_name"],$data["category_id"],$data["origin_id"],$data["instructions"],$id]);$pdo->prepare("DELETE FROM food_ingredients WHERE food_id=?")->execute([$id]);$i=$pdo->prepare("INSERT INTO food_ingredients(food_id,ingredient_id) VALUES(?,?)");foreach($data["ingredient_ids"] as $iid){$i->execute([$id,$iid]);}$pdo->commit();return jsonResponse($response,["status"=>"success","message"=>"Food updated successfully."]);}catch(PDOException $e){$pdo->rollBack();return jsonResponse($response,["status"=>"error","message"=>$e->getMessage()],500);}
});
/* ── DELETE /api/foods/{id} ── */
$group->delete('/foods/{id}', function (Request $request, Response $response, array $args) {
$pdo=getDbConnection();$id=(int)$args['id'];$s=$pdo->prepare("SELECT * FROM foods WHERE food_id=?");$s->execute([$id]);if(!$s->fetch()) return jsonResponse($response,["status"=>"error","message"=>"Food not found"],404);
try{$pdo->beginTransaction();$pdo->prepare("DELETE FROM food_ingredients WHERE food_id=?")->execute([$id]);$pdo->prepare("DELETE FROM foods WHERE food_id=?")->execute([$id]);$pdo->commit();return jsonResponse($response,["status"=>"success","message"=>"Food deleted successfully."]);}catch(PDOException $e){$pdo->rollBack();return jsonResponse($response,["status"=>"error","message"=>$e->getMessage()],500);}
});

})
->add($authMiddleware)        // inner  — runs 2nd on request
->add($rateLimitMiddleware);  // outer  — runs 1st on request

$app->run();
