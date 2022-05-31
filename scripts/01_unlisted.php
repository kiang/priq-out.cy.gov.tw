<?php
$rootPath = dirname(__DIR__);
$pool = [];
$max = 0;
foreach (glob($rootPath . '/data/list/*/*.csv') as $csvFile) {
    $fh = fopen($csvFile, 'r');
    fgetcsv($fh, 2048);
    while ($line = fgetcsv($fh, 2048)) {
        if (!empty($line[5])) {
            $parts = explode('=', $line[5]);
            $key = array_pop($parts);
            $pool[$key] = true;
            if ($key > $max) {
                $max = $key;
            }
        }
    }
}

$downloadCounter = 0;
for ($i = 1; $i <= $max; $i++) {
    if (isset($pool[$i])) {
        continue;
    }
    $no = ceil($i / 500) * 500;
    $filePath = $rootPath . '/raw/unlisted/' . $no;
    if (!file_exists($filePath)) {
        mkdir($filePath, 0777, true);
    }
    $targetFile = $filePath . '/' . $i . '.pdf';
    if (!file_exists($targetFile)) {
        $link = 'https://priq-out.cy.gov.tw/GipExtendWeb/wSite/SpecialPublication/fileDownload.jsp?id=' . $i;
        $c = file_get_contents($link);
        if (strlen($c) > 100) {
            echo "{$targetFile} done\n";
            file_put_contents($targetFile, $c);
            ++$downloadCounter;
        }
    }
    if ($downloadCounter === 500) {
        $downloadCounter = 0;
        $now = date('Y-m-d H:i:s');
        exec("cd {$rootPath} && /usr/bin/git pull");
        exec("cd {$rootPath} && /usr/bin/git add -A");
        exec("cd {$rootPath} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m 'auto update @ {$now}'");
        exec("cd {$rootPath} && /usr/bin/git push origin master");
    }
}

if ($downloadCounter > 0) {
    $now = date('Y-m-d H:i:s');
    exec("cd {$rootPath} && /usr/bin/git pull");
    exec("cd {$rootPath} && /usr/bin/git add -A");
    exec("cd {$rootPath} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m 'auto update @ {$now}'");
    exec("cd {$rootPath} && /usr/bin/git push origin master");
}
