<?php
// web/index.php
use Doctrine\DBAL\Connection;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . preg_replace('/(\?.*)$/', '', $_SERVER['REQUEST_URI']))) {
    return false;
}

require_once __DIR__ . '/vendor/autoload.php';

Request::enableHttpMethodParameterOverride();

$app = (new Silex\Application(['debug' => true]))

    // база
    ->register(new DoctrineServiceProvider(),
        ['db.options' => ['driver' => 'pdo_mysql', 'dbname' => 'traps', 'charset' => 'utf8']]);

// ... definitions
$app->get('/getTraps/', function () use ($app) {
    /**@var $conn Connection */
    $conn = $app['db'];
    $Traps = $conn->fetchAll('select * from trap');
    return json_encode($Traps);

    # можно вернуть объект ответа, как и в Symfony
    # return new Symfony\Component\HttpFoundation\Response('Hello, world');
});

// Добавление ловушки

$app->post('/add', function (Request $req) use ($app) {
    /**@var $conn Connection */
    $conn = $app['db'];
    $barCode = $req->get('barCode'); // штрих код

    list($traceBittes, $adhesivePlateReplacement, $numberPests, $isTrapDamage, $isTrapReplacement, $isTrapReplacementDo, $photo) = getRequests($req);

    $result = $conn->insert('trap',
        [
            'barCode' => $barCode,
            'traceBittes' => $traceBittes,
            'adhesivePlateReplacement' => $adhesivePlateReplacement,
            'numberPests' => $numberPests,
            'isTrapDamage' => $isTrapDamage,
            'isTrapReplacement' => $isTrapReplacement,
            'isTrapReplacementDo' => $isTrapReplacementDo,
            'photo' => $photo
        ]);


    return $result;
});

$app->post('/edit', function (Request $request) use ($app) {
    /**@var $conn Connection */
    $conn = $app['db'];
    $update = $conn->prepare("UPDATE trap set traceBittes=:traceBittes, adhesivePlateReplacement = :adhesivePlateReplacement,
 numberPests = :numberPests, isTrapDamage=:isTrapDamage, 
  isTrapReplacement=:isTrapReplacement, isTrapReplacementDo=:isTrapReplacementDo, photo=:photo   where id=:id");

    $update->bindParam(':id', $id);
    $update->bindParam(':traceBittes', $traceBittes);
    $update->bindParam(':adhesivePlateReplacement', $adhesivePlateReplacement);
    $update->bindParam(':numberPests', $numberPests);
    $update->bindParam(':isTrapDamage', $isTrapDamage);
    $update->bindParam(':isTrapReplacement', $isTrapReplacement);
    $update->bindParam(':isTrapReplacementDo', $isTrapReplacementDo);
    $update->bindParam(':photo', $photo);

    $id = $request->get('id');
    list($traceBittes, $adhesivePlateReplacement, $numberPests, $isTrapDamage, $isTrapReplacement, $isTrapReplacementDo, $photo) = getRequests($request);

    return $update->execute();
});

$app->get('/find', function (Request $request) use ($app) {
    /**@var $conn Connection */
    $conn = $app['db'];

    $barCode = $request->get('barCode');
    echo $barCode . "\n";
    $Trap = $Traps = $conn->fetchAll('select * from trap where barCode = ' . "\"" . $barCode . "\"");
    echo 'select * from trap where barCode = ' . $barCode . "\n";
    return json_encode($Trap);
});
/**
 * @param Request $request
 * @return array
 */
function getRequests(Request $req)
{
    $traceBittes = $req->get('traceBittes'); // следы прогрызов
    $adhesivePlateReplacement = $req->get('adhesivePlateReplacement'); //  	Произведена замена клеевой пластины (да/нет)
    $numberPests = $req->get('numberPests'); //  	Количество вредителей в ловушке ( число)
    $isTrapDamage = $req->get('isTrapDamage'); //  	Ловушка повреждена (да/нет)
    $isTrapReplacement = $req->get('isTrapReplacement'); // Нужна замена ловушки(да/нет)
    $isTrapReplacementDo = $req->get('isTrapReplacementDo');    // Произведена ли замена ловушки (да/нет)
    $photo = $req->get('photo');
    return array($traceBittes, $adhesivePlateReplacement, $numberPests, $isTrapDamage, $isTrapReplacement, $isTrapReplacementDo, $photo);
}

$app->run();

