<?php

   $wwwDir = '/var/www';

    $excludeUserDirs = [
        '/var/www/cgi-bin',
        '/var/www/html',
        '/var/www/httpd-logs',
        '/var/www/php-bin',
    ];

    $excludeSites = [
        //'domain.com',
    ];
            
    $useIgnoreMasks = true;
            
    $ignoreMasks = [
        'all' => [
            'can\'t apply process slot for',
            'can\'t lock process table in pid',
            'ap_pass_brigade failed in handle_request_ipc function',
            'read data timeout in \d+ seconds',
            'Invalid method in request get',
            'error reading data from FastCGI server',
            'script not found or unable to stat',
            'client denied by server configuration',
            'can\'t get data from http client',
            'No matching DirectoryIndex \([^)]+\) found',
            'End of script output before headers',
            'AH00135: Invalid method in request',
            'AH00126: Invalid URI in request',
            'AH00036: access to',
            'AH00127: Cannot map GET',
            
        ],
        'domain.com' => [
            'mod_fcgid: stderr: CURL ERR',
        ],
    ];
                    
    $mailConfig = [
        'notify_mail' => true,
        'notify_mail_email' => 'notify@admin.com',
        'notify_mail_subj' => 'DXHZ: Errors report',
        'notify_mail_from' => 'no-reply@dxhz.com',
    ];
                    
    // ===================================================================================
                     
    $notify = function($msg) use ($mailConfig) {
        if ($mailConfig['notify_mail']) {
            $headers = 'From: '.$mailConfig['notify_mail_from'] . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();
            $message = 'Server: DXHZ'.PHP_EOL
                     .'Date: '.date('d.m.Y H:i:s').PHP_EOL.PHP_EOL
                     .ltrim($msg)
            ;
            mail( $mailConfig['notify_mail_email'],
                  $mailConfig['notify_mail_subj'],
                  $message,
                  $headers
            );
        }
    };

    // --------------------------------
    
    $dirs = glob($wwwDir.'/*');
    
    $usersDirs = [];

    foreach($dirs as $dir) {
        if (is_dir($dir) AND !in_array($dir, $excludeUserDirs)) {
            $usersDirs[] = $dir;
        }
    }
    
    $logFilesPathes = [];

    foreach ($usersDirs as $baseDir) {
        $logs = glob($baseDir.'/data/logs/*.error.log');
        foreach ($logs as $logFilePath) {
            $siteName = preg_replace('~^.+/([^/]+)\.error\.log$~','$1',$logFilePath);
            if (in_array($siteName, $excludeSites)) continue;
            $logFilesPathes[$siteName] = $logFilePath;
        }
    }
    
    $errors = [];
    foreach ($logFilesPathes as $siteName=>$file) {
        if ($records = file($file)) {
            foreach($records as $rec) {
                if ($useIgnoreMasks) {
                    $ignoreRec = false;
                    foreach($ignoreMasks['all'] as $mask) {
                        if (preg_match('~'.$mask.'~', $rec)) {
                            $ignoreRec = true;
                            break;
                        }
                    }
                    if (!empty($ignoreMasks[$siteName])) {
                        foreach($ignoreMasks[$siteName] as $mask) {
                            if (preg_match('~'.$mask.'~', $rec)) {
                                    $ignoreRec = true;
                                break;
                                }
                        }
                    }
                    
                    if ($ignoreRec) continue;
                }
                
                $msg = trim(preg_replace('~^.+\]([^\[\]]+)$~','$1',$rec));
                $msg = preg_replace('~(,\sreferer:)\s\S+~','', $msg);
                if (!isset($errors[$siteName][$msg])) {
                    $errors[$siteName][$msg]=1;
                } else {
                    $errors[$siteName][$msg]++;
                }
            }
        }
    }
    
    if ($errors) {
        $mailMsg='================================================'.PHP_EOL.PHP_EOL;
        foreach ($errors as $siteName=>$items) {
            $mailMsg.='Report for site: '.$siteName.PHP_EOL.PHP_EOL;
            $mailMsg.='--------'.PHP_EOL;
            $total = count($items);
            foreach($items as $msg=>$count) {
                $mailMsg.='Err cnt: '.$count.PHP_EOL;
                $mailMsg.='Msg: '.$msg.PHP_EOL;
                if (--$total>0) $mailMsg.='--------'.PHP_EOL;
            }
            $mailMsg.=PHP_EOL.'================================================'.PHP_EOL.PHP_EOL;
        }
        $notify($mailMsg);
    }

  if ($errors) {
        print_r($errors);
    } else {
        echo 'No errors'.PHP_EOL;
    }
