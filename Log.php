<?php

class Log {
    
    const LOG_ERROR = 'error';
    const LOG_DEBUG = 'debug';
    
    public static function add($level, $message) {
        $vargs = array_slice(func_get_args(), 2);
        if(count($vargs) > 0) {
            $params = array_merge(array($message), $vargs);
            $message = call_user_func_array('sprintf', $params);
        }
        
       printf("%s: [%s] %s\n", strtoupper($level), date('Y-m-d H:i:s'), $message);
    }
    
    public static function debug($message) {
        $args = func_get_args();
        array_unshift($args, self::LOG_DEBUG);
        call_user_func_array('Log::add', $args);
    }
    
    public static function error($message) {
        $args = func_get_args();
        array_unshift($args, self::LOG_ERROR);
        call_user_func_array('Log::add', $args);
    }
}

?>
