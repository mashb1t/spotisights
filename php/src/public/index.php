<?php

use App\Factory;

require __DIR__ . '/../vendor/autoload.php';

session_start();
?>
<!doctype html>
<html lang="en-us">
<head>
    <title>SpotiSights Connect</title>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <style type="text/css">
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            /*font-size: 1rem;*/
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
        }

        .card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: #fff;
            background-clip: border-box;
            border: 1px solid rgba(0, 0, 0, .125);
            border-radius: 0.25rem;
            padding: 1rem 1rem;
        }

        .card img {
            max-width: 100%;
            margin-bottom: 1rem;
        }

        .card-body {
            flex: 1 1 auto;
            /*padding: 1rem 1rem;*/
        }

        /*.card-title {*/
        /*    margin-bottom: 0.5rem;*/
        /*}*/

        .col-md-4 {
            padding-bottom: 1.5rem;
        }

        p {
            margin-top: 0;
            margin-bottom: 0;
        }
    </style>
</head>

<body>
<div class="container">
    <h1>SpotiSights Connect</h1>
</div>

<?php $factory = new Factory() ?>

<div class="container">
    <div class="row">
        <?php foreach (explode(',', getenv('ACTIVE_SERVICES')) as $serviceName): ?>
            <?php $session = $factory->getSession($serviceName); ?>
            <div class="col-md-4">
                <div class="card">
                    <img src="images/services/<?= $serviceName ?>.png" class="card-img-top" alt="<?= ucfirst($serviceName) ?>">
                    <div class="card-body">
                        <p class="card-text">
                            <?php if (!isset($_SESSION['logged_in'][$serviceName])): ?>
                                <a href="<?= $session->getLoginUrl(); ?>" class="btn btn-primary">Connect <?= ucfirst($serviceName) ?></a>
                            <?php else: ?>
                                Username: <?= $_SESSION[$serviceName . '_username'] ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (isset($_SESSION['logged_in']) && count($_SESSION['logged_in']) > 0): ?>
    <div class="container">
        <button type="button" class="btn btn-primary" onclick="location.href='<?= getenv('GRAFANA_DASHBOARD_URL') ?>';">
            Show Statistics
        </button>
    </div>
<?php endif; ?>
</body>
</html>
