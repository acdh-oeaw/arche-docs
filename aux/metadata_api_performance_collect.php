<?php
$composer = include 'vendor/autoload.php';
$c = new GuzzleHttp\Client(['http_errors' => false]);
$L = '/var/www/html/log/rest.log';
$B = 'http://127.0.0.1/api';
$N = 5;
$R = [46266,105024,66224,105858,46441,75232,67345];
$F = ['text/turtle','application/n-triples','application/ld+json','application/rdf+xml'];
$V = Composer\InstalledVersions::getVersion('acdh-oeaw/arche-core');
foreach ($R as $r) {
    foreach ($F as $f) {
        $f = rawurlencode($f);
        $req = new GuzzleHttp\Psr7\Request('GET', "$B/$r/metadata?format=$f");
        echo "$r\t$f\t$V\t";
        $tt = $tn = 0;
        $mu = [];
        for ($n = 0; $n < $N; $n++) {
            if (file_exists($L)) {
                unlink($L);
            }
            $t = microtime(true);
            $resp = $c->send($req);
            $t = microtime(true) - $t;
            if ($resp->getStatusCode() === 200) {
                $mu[] = preg_replace('/.*Memory usage ([0-9]+) .*/s', '\\1', file_get_contents($L));
                $tt += $t;
                $tn++;
            }
        }
        sort($mu);
        $mu = $mu[(int) (count($mu) / 2)] ?? '';
        echo "$tn\t".($tn > $N / 2 ? $tt / $tn : '')."\t$mu\n";
    }
}

