<?php
$token ='';

function csrf_token()
{
   $token = bin2hex( random_bytes(32));
   $_SESSION['token'] = $token;
   return $token;
}

if (isset($_POST['token']) && empty($_POST['token']))

{
  $error .= "<div class='alert alert-danger text-center' role='alert'>CSRF token missing</div>";

}
