<?php

include('tareas.php');//Trae las tareas puntuales que vamos a ejecutar aqui

//Crecion del archivo log 

$file_name = 'logs.txt';
//crea el log 
 CrearArchivoLog($file_name)."<br>";

//1. Se trae el token de sesion de GLPI

InsertarEntradaLog($file_name, 'Inicia operacion');

$DataTokenSession = TraerSessionToken();

$jsonSessionArray = json_decode($DataTokenSession, true);

if ($jsonSessionArray !== null && isset($jsonSessionArray["session_token"])) 
{
	$sessionToken = $jsonSessionArray["session_token"];

	//2. Se trae los usuarios de GLPI

    $usuariosGlpi = TraerUsuariosGlpi($sessionToken);
    $jsonUsuariosGlpi = json_decode($usuariosGlpi, true);

	//3. Traer los usuarios de Ldap

    $usuariosLdap = TraerUsuariosLdap();

	//4. Valida Creacion de usuarios

    $resultadoCorreo =  ColocarCorreosUsuarios($jsonUsuariosGlpi, $usuariosLdap, $sessionToken); 

    echo $resultadoCorreo;

    InsertarEntradaLog($file_name, $resultadoCorreo); 
	
    $CerrarSesion = CerrarSessionToken($sessionToken);

	InsertarEntradaLog($file_name, $sessionToken); 

	echo 'Operacion finalizada con éxito.<br>';
} 
else 
{
    $error = "No es pósible traer un token de sesión para esta ejecucíon: " . $sessionData.". <br> La ejecucíon no pudo ser realizada.";

    echo  $error.'<br>';

    InsertarEntradaLog($file_name, $error); 
}

InsertarEntradaLog($file_name, 'Fin de operacion'); 


?>