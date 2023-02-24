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

$missingFile = $rootPath . '/raw/unlisted/missing.csv';
if (file_exists($missingFile)) {
    $fh = fopen($missingFile, 'r');
    while ($line = fgets($fh, 512)) {
        $pool[trim($line)] = true;
    }
}

$downloadCounter = 0;
$currentNo = 0;
for ($i = 1; $i <= $max; $i++) {
    $no = ceil($i / 500) * 500;
    if ($currentNo !== $no) {
        if ($currentNo > 0) {
            $io = popen('/usr/bin/du -sk ' . $rootPath . '/raw/unlisted/' . $currentNo, 'r');
            $size = fgets($io, 4096);
            $size = substr($size, 0, strpos($size, "\t"));
            pclose($io);
            if ($size < 5 && file_exists($rootPath . '/raw/unlisted/' . $currentNo)) {
                rmdir($rootPath . '/raw/unlisted/' . $currentNo);
            }
        }
        $currentNo = $no;
    }
    if (isset($pool[$i])) {
        continue;
    }
    $filePath = $rootPath . '/raw/unlisted/' . $no;
    if (!file_exists($filePath)) {
        mkdir($filePath, 0777, true);
    }

    $targetFile = $filePath . '/' . $i . '.pdf';
    if (!file_exists($targetFile)) {
        $link = 'https://priq-out.cy.gov.tw/GipExtendWeb/wSite/SpecialPublication/fileDownload.jsp?id=' . $i;
        $c = file_get_contents($link);
        if (strlen($c) > 100) {
            file_put_contents($targetFile, $c);
            ++$downloadCounter;
            echo "[{$downloadCounter}]{$targetFile} done\n";
        } else {
            $oFh = fopen($missingFile, 'a+');
            fputs($oFh, $i . "\n");
            fclose($oFh);
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
