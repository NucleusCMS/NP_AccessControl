<?php
if(!function_exists('parseHtml')) {
    
    function parseHtml($html='',$ph=array()) { // $ph is placeholders
        if(!is_array($ph)) {
            $ph = func_get_args();
        }
        
        $esc = md5($_SERVER['REQUEST_TIME_FLOAT'].mt_rand());
        
        foreach($ph as $k=>$v) {
            if(strpos($html,'{%')===false) break;
            
            if(strpos($v,'{%')!==false) {
                $v = str_replace('{%',"[{$esc}%",$v);
            }
            $html = str_replace("{%{$k}%}", $v, $html);
            if(strpos($html,"{%{$k}:hsc%}")!==false)
            {
                $html = str_replace("{%{$k}:hsc%}", hsc($v), $html);
            }
            if(strpos($html,"{%{$k}:urlencode%}")!==false)
            {
                $html = str_replace("{%{$k}:urlencode%}", urlencode($v), $html);
            }
        }
        if(strpos($html,'{'.$esc.'%')!==false) {
            $html = str_replace('{'.$esc.'%','{%',$html);
        }
        return $html;
    }
    
}