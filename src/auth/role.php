<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function requireRole(String $user):void{
    if(($_SESSION["role"]??"") != $user){
        http_response_code(403);
        echo "Kamu gak punya akses ke halaman ini";
        exit;
    }
}
?>