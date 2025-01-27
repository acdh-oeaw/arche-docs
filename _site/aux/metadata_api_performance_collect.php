<?php
$composer = include 'vendor/autoload.php';
$c = new GuzzleHttp\Client(['http_errors' => false]);
$L = '/home/www-data/log/rest.log';
$B = 'http://127.0.0.1/api';
$N = 5;
$R = [46266,105024,66224,105858,46441,75232,67345];
$F = ['application/n-triples','text/turtle','application/ld+json','application/rdf+xml'];
$V = Composer\InstalledVersions::getVersion('acdh-oeaw/arche-core');
$RTC = [];
foreach ($R as $r) {
    $r = (string) $r;
    foreach ($F as $f) {
        echo "$r\t$f\t$V\t";
        $req = new GuzzleHttp\Psr7\Request('GET', "$B/$r/metadata?format=".rawurlencode($f)."&readMode=neighbors");
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
            if (!isset($RTC[$r]) && $f === 'application/n-triples') {
                $RTC[$r] = substr_count((string) $resp->getBody(), "\n");
            }
        }
        sort($mu);
        $mu = $mu[(int) (count($mu) / 2)] ?? '';
        echo $RTC[$r]."\t$tn\t".($tn > $N / 2 ? $tt / $tn : '')."\t$mu\n";
    }
}

