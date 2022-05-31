<?php
$rootPath = dirname(__DIR__);
$rawPath = $rootPath . '/raw';
$pagePath = $rawPath . '/page';
if (!file_exists($pagePath)) {
    mkdir($pagePath, 0777, true);
}
$listPath = $rootPath . '/data/list';
$pageFullFile = $pagePath . '/20220531.html';
if (!file_exists($pageFullFile)) {
    file_put_contents($pageFullFile, file_get_contents('https://priq-out.cy.gov.tw/GipExtendWeb/wSite/SpecialPublication/SpecificLP.jsp?nowPage=1&perPage=500&queryStr=&queryCol=period'));
}

$pageFull = file_get_contents($pageFullFile);
$downloadCounter = 0;
$oFh = [];

$lines = explode('<tr>', $pageFull);
foreach ($lines as $line) {
    $cols = explode('</td>', $line);
    if (count($cols) === 6) {
        $parts = explode('\'', $cols[1]);
        $key = $parts[1];
        foreach ($cols as $k => $v) {
            $cols[$k] = preg_replace('/\s/', '', strip_tags($v));
        }
        $theDay = preg_split('/[^0-9]+/', $cols[3]);
        $theDay[1] += 1911;
        $theDate = $theDay[1] . $theDay[2] . $theDay[3];
        $periodPath = $rawPath . '/period/' . $theDate . '_' . $cols[1];
        if (!file_exists($periodPath)) {
            mkdir($periodPath, 0777, true);
        }
        $periodListFile = $periodPath . '/list.html';
        if (!file_exists($periodListFile)) {
            file_put_contents($periodListFile, file_get_contents('https://priq-out.cy.gov.tw/GipExtendWeb/wSite/SpecialPublication/baseList.jsp?nowPage=1&perPage=300&queryStr=' . $key . '&queryCol=period'));
        }
        $periodList = file_get_contents($periodListFile);
        $pLines = explode('</tr>', $periodList);
        foreach ($pLines as $pLine) {
            $pCols = explode('</td>', $pLine);
            if (8 === count($pCols)) {
                $pKeys = preg_split('/[^0-9]+/', $pCols[1]);
                if (!empty($pKeys[1])) {
                    foreach ($pCols as $k => $v) {
                        $pCols[$k] = preg_replace('/\s/', '', strip_tags($v));
                    }
                    $pCols[3] = str_replace('/', '_', $pCols[3]);
                    $link = 'https://priq-out.cy.gov.tw/GipExtendWeb/wSite/SpecialPublication/fileDownload.jsp?id=' . $pKeys[1];
                    if (!isset($oFh[$pCols[3]])) {
                        $fileParts = explode('_', $pCols[3]);
                        $filePath = $listPath . '/' . $fileParts[0];
                        if (!file_exists($filePath)) {
                            mkdir($filePath, 0777, true);
                        }
                        $oFh[$pCols[3]] = $filePath . '/' . $fileParts[1] . '.csv';
                        $wFh = fopen($oFh[$pCols[3]], 'w');
                        fputcsv($wFh, ['date', 'period', 'name', 'title', 'type', 'link']);
                        fclose($wFh);
                    }
                    $wFh = fopen($oFh[$pCols[3]], 'a');
                    fputcsv($wFh, [$theDate, $cols[1], $pCols[1], $pCols[3], $pCols[4], $link]);
                    fclose($wFh);
                    $pdfFile = $periodPath . '/' . $pCols[3] . '_' . $pCols[1] . '.pdf';
                    if (!file_exists($pdfFile)) {
                        echo "getting {$pdfFile}\n";
                        $c = file_get_contents($link);
                        if (strlen($c) > 100) {
                            file_put_contents($pdfFile, $c);
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
            }
        }
    }
}

if ($downloadCounter > 0) {
    $now = date('Y-m-d H:i:s');
    exec("cd {$rootPath} && /usr/bin/git pull");
    exec("cd {$rootPath} && /usr/bin/git add -A");
    exec("cd {$rootPath} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m 'auto update @ {$now}'");
    exec("cd {$rootPath} && /usr/bin/git push origin master");
}
