<?php

include('api_glpi_config.php');
include('ldap_config.php');
include('db_config.php');


//Trae los usuarios de LDAP

function TraerUsuariosLdap()
{
    global $ldap_server, $ldap_user, $ldap_pass;
    
    $ldap_connection = ldap_connect($ldap_server);

    if (!$ldap_connection) {
        die("No se pudo conectar al servidor LDAP.");
    }

    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);

    if (ldap_bind($ldap_connection, $ldap_user, $ldap_pass)) {
        $ldap_base_dn = "dc=coltrans,dc=local"; // Reemplaza con el DN de la base donde se encuentran los usuarios
        $ldap_filter = "(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))";
        $ldap_attributes = array("cn", "samaccountname", "mail","objectguid");

        $ldap_search = ldap_search($ldap_connection, $ldap_base_dn, $ldap_filter, $ldap_attributes);
        $ldap_entries = ldap_get_entries($ldap_connection, $ldap_search);

        $usuarios = array(); // Creamos un array para almacenar los usuarios

        if ($ldap_entries['count'] > 0) {
            for ($i = 0; $i < $ldap_entries['count']; $i++) {
                $username = !empty($ldap_entries[$i]['samaccountname'][0]) ? $ldap_entries[$i]['samaccountname'][0] : "No aplica";
                $commonName = !empty($ldap_entries[$i]['cn'][0]) ? $ldap_entries[$i]['cn'][0] : "No aplica";
                $email = !empty($ldap_entries[$i]['mail'][0]) ? $ldap_entries[$i]['mail'][0] : "No aplica";
                $ObjectGuid = !empty($ldap_entries[$i]['objectguid'][0]) ? $ldap_entries[$i]['objectguid'][0] : "No aplica";
                $baseDn = 0;


                $binguid = bin2hex($ObjectGuid);

                $formattedGUID = substr($binguid, 6, 2) . substr($binguid, 4, 2) . substr($binguid, 2, 2) . substr($binguid, 0, 2) . '-' .
                 substr($binguid, 10, 2) . substr($binguid, 8, 2) . '-' .
                 substr($binguid, 14, 2) . substr($binguid, 12, 2) . '-' .
                 substr($binguid, 16, 4) . '-' .
                 substr($binguid, 20);


                // Almacenamos los datos en el array
                $usuario = array(
                    'Usuario' => $username,
                    'NombreComun' => $commonName,
                    'CorreoElectronico' => $email,
                    'objectGUID' => $formattedGUID,
                    'baseDn' => $baseDn 

                );
                $usuarios[] = $usuario;
            }
        }

        ldap_close($ldap_connection);

        return $usuarios; // Devolvemos el array de usuarios
    } else {
        echo "Autenticación fallida.";
    }
}

//Crear Token de sesion en GLPI

function TraerSessionToken()
{
    global $base_url, $api_token, $user_token;

    $url = $base_url . "/apirest.php/initSession";

    $headers = array(
        "Content-Type: application/json",
        "Authorization: user_token ".$user_token,
        "App-Token: " . $api_token
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if ($response === false) {
        return "Error al hacer la solicitud: " . curl_error($ch);
    } else {
        return $response; // La respuesta es un JSON con el token de sesión
    }

    curl_close($ch);
}

//Destruir Token de sesion en GLPI

function CerrarSessionToken($sessionToken)
{
    global $base_url, $api_token;

    $url = $base_url . "/apirest.php/killSession";

    $headers = array(
        "Content-Type: application/json",
        "Session-Token: ".$sessionToken,
        "App-Token: " . $api_token
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if ($response === false) {
        return "Error al hacer la solicitud: " . curl_error($ch);
    } else {
         // Decodificar la respuesta JSON
        $responseArray = json_decode($response, true);

        if ($responseArray === null) 
        {
            return "Respuesta no válida: " . $response.".";
        } 
        elseif ($responseArray === true) 
        {
            return "La sesión se cerró con éxito.";
        } 
        elseif (is_array($responseArray) && isset($responseArray[0])) 
        {
            return "Error: " . $responseArray[0];
        } 
        else 
        {
            return "Respuesta inesperada: " . $response.".";
        }
    }

    curl_close($ch);
}

//Traer Usuarios desde GLPI

function TraerUsuariosGlpi($sessionToken)
{
    global $base_url, $api_token;

    $url = $base_url . "/apirest.php/User/?range=1-1000&is_deleted=0";
    //$url = $base_url . "/apirest.php/User/?range=1-1000&is_deleted=0";

    $headers = array(
        "Content-Type: application/json",
        "Session-Token: ".$sessionToken,
        "App-Token: " . $api_token
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if ($response === false) {
        return "Error al hacer la solicitud: " . curl_error($ch);
    } else {
        return $response; // La respuesta es un JSON con el token de sesión
    }

    curl_close($ch);
}

//Compara la informacion de los arreglos de usuarios 

function CompararUsuariosNuevos($UsuariosGlpi, $UsuariosLdap,$sessionToken)
{   
    $contadorUsuariosCreados = 0;
    $contadorUsuariosModificados = 0;

    //Carga los usuarios del ldap
    if (!empty($UsuariosLdap)) 
    {
        echo '<p>Validacion de Ldap</p>';

        foreach ($UsuariosLdap as $usuarioLdap) 
        {
            
            $NombreUsuarioLdap = $usuarioLdap['Usuario'];
            $NombreCompletoLdap = $usuarioLdap['NombreComun'];
            $CorreoElectronicoLdap =  $usuarioLdap['CorreoElectronico'];
            $ObjectGuid =  $usuarioLdap['objectGUID'];
            $baseDn = $usuarioLdap['baseDn'];

            //echo "$NombreUsuarioLdap | $NombreCompletoLdap | $CorreoElectronicoLdap | $ObjectGuid |  $baseDn <br>";       

            if (!in_array($NombreUsuarioLdap, array_column($UsuariosGlpi, "name")))
            {
                
                echo "$NombreUsuarioLdap no se encuentra en el GLPI. <br>";

                $userData = [
                    'name' => $NombreUsuarioLdap,
                    'realname' => $NombreCompletoLdap,
                    'email' => $CorreoElectronicoLdap,
                    'ldap_import' => 1, // Esta bandera indica que el usuario es importado desde LDAP
                    'auths_id' => 2,
                    'authtype' => 3, // ID del método de autenticación LDAP en GLPI
                    'is_active' => 1,
                    'sync_field' => $ObjectGuid// ID del perfil "Self Service" 
                ];

                $CreacionUsuarioGlpi = CreaUsuarioGlpi($sessionToken,$userData);

                $jsonCreacionUsuarioGlpi = json_decode($CreacionUsuarioGlpi, true);

                // Verificar si la decodificación fue exitosa
                if ($jsonCreacionUsuarioGlpi == null) {
                    echo 'Error al decodificar el JSON';
                } 
                else 
                {
                    if (is_array($jsonCreacionUsuarioGlpi) && isset($jsonCreacionUsuarioGlpi['id'])){
                        // Acceder directamente a los valores
                        $contadorUsuariosCreados++;
                        $idGlpiNuevo = $jsonCreacionUsuarioGlpi['id'];
                        $message = $jsonCreacionUsuarioGlpi['message'];

                        //echo "ID: $id, Mensaje: $message";

                        $idPerfil = 1; 

                        $PerfilData = array(
                            "profiles" => array($idPerfil)
                        );

                        $VinculoPerfil = VinculaPerfilGlpi($sessionToken,$PerfilData, $idGlpiNuevo);

                        echo $VinculoPerfil."<br>";

                    }
                    elseif (is_array($jsonCreacionUsuarioGlpi) && count($jsonCreacionUsuarioGlpi) == 2) 
                    {
                        // Acceder a los valores de una lista (array)
                        $errorCode = $jsonCreacionUsuarioGlpi[0];
                        $errorMessage = $jsonCreacionUsuarioGlpi[1];
                        echo "Código de error: $errorCode, Mensaje de error: $errorMessage<br>";
                    } 
                    else 
                    {
                        echo 'JSON no reconocido<br>';
                    }
                    
                }
            } 
        }
        return "$contadorUsuariosCreados Usuarios Creados";
    } 
    else 
    {
        return "No se encontraron usuarios en directorio activo.";
    }

}

//Compara usuarios existentes

function CompararUsuariosExistentes($UsuariosGlpi, $UsuariosLdap,$sessionToken)
{   
    $contadorUsuariosModificados = 0;

    //Carga usuarios Glpi
    if (is_array($UsuariosGlpi)) 
    {
        foreach ($UsuariosGlpi as $UsuarioGlpi) 
        {
            
            $IdUsuarioGlpi = $UsuarioGlpi['id'];
            $NombreCompletoGlpi = $UsuarioGlpi['name'];
            $estadoGlpi =  $UsuarioGlpi['is_active'];

            //echo $estadoGlpi."<br>";

            if (!in_array($NombreCompletoGlpi, array_column($UsuariosLdap, "Usuario")))
            {
                //Actualizar estado 

                $UsuarioExistenteData =
                [
                    'is_active' => false
                ];

                if($estadoGlpi != 0){

                    //echo "$NombreCompletoGlpi no se encuentra en el Ldap. <br>";

                    
                    $CambioEstado= CambiarEstadoGlpi($sessionToken,$IdUsuarioGlpi,$UsuarioExistenteData);

                    $contadorUsuariosModificados++;
                    //echo  "$contadorUsuariosModificados. $NombreCompletoGlpi: $CambioEstado<br>";
                }
            }                      
        }
        return "$contadorUsuariosModificados Usuarios Modificados<br>";       
    } 
    else 
    {
        // Maneja el caso en el que no se pudo decodificar el JSON correctamente
        return "Error al decodificar el JSON de los usuarios de Glpi.";
    }

}
//Vinculacion de correo
function ColocarCorreosUsuarios($UsuariosGlpi, $UsuariosLdap,$sessionToken)
{   
   $contadorCorreos = 0;

    //Carga usuarios Glpi
    if (is_array($UsuariosGlpi)) 
    {
        foreach ($UsuariosGlpi as $UsuarioGlpi) 
        {
           

            $IdUsuarioGlpi = $UsuarioGlpi['id'];
            $NombreCompletoGlpi = $UsuarioGlpi['name'];

            foreach ($UsuariosLdap as $usuarioLdap) 
            {
            
                $NombreUsuarioLdap = $usuarioLdap['Usuario'];
                $CorreoElectronicoLdap =  $usuarioLdap['CorreoElectronico'];

                if($CorreoElectronicoLdap != "No aplica")
                {
                    if($NombreUsuarioLdap == $NombreCompletoGlpi)
                    {
                        $CorreosData = [
                            [
                                "users_id" => $IdUsuarioGlpi,
                                "email" => $CorreoElectronicoLdap,
                                "is_default" => 1
                            ]
                        ];
                    

                        //echo  $DataCorreo;

                        $respuesta = ColocarCorreoUsuario($sessionToken,$CorreosData);

                        echo  $IdUsuarioGlpi." -> ".$NombreCompletoGlpi." -> ".$respuesta."<br>";

                        $contadorCorreos++;
                        //echo  $IdUsuarioGlpi." -> ".$NombreUsuarioLdap." -> ".$CorreoElectronicoLdap."<br>";
                        //print_r($CorreosData);
                    }

                }

              

            }
                     
        }
        return "$contadorCorreos Correos Modificados<br>";       
    } 
    else 
    {
        // Maneja el caso en el que no se pudo decodificar el JSON correctamente
        return "Error al decodificar el JSON de los usuarios de Glpi.";
    }

}

//Creacion usuarios en GLPI

function CreaUsuarioGlpi($sessionToken,$userData)
{
    global $base_url, $api_token;

    $url = $base_url . "/apirest.php/User/";
    //$url = $base_url . "/apirest.php/User/?range=1-1000&is_deleted=0";

    $headers = array(
        "Content-Type: application/json",
        "Session-Token: ".$sessionToken,
        "App-Token: " . $api_token
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['input' => $userData]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if ($response === false) {
        return "Error al hacer la solicitud: " . curl_error($ch);
    } else {
        return $response; // La respuesta es un JSON con el token de sesión
    }

    curl_close($ch);
}


//Actualizar estado Usuario

function CambiarEstadoGlpi($sessionToken,$IdUsuarioGlpi,$UsuarioExistenteData)
{
    global $base_url, $api_token;

    $url = $base_url . "/apirest.php/User/$IdUsuarioGlpi";

    //echo $url;
    //$url = $base_url . "/apirest.php/User/?range=1-1000&is_deleted=0";

    $headers = array(
        "Content-Type: application/json",
        "Session-Token: ".$sessionToken,
        "App-Token: " . $api_token
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['input' => $UsuarioExistenteData]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if ($response === false) {
        return "Error al hacer la solicitud: " . curl_error($ch);
    } else {
        return $response; // La respuesta es un JSON con el token de sesión
    }

    curl_close($ch);
}
//
function ColocarCorreoUsuario($sessionToken,$CorreosData)
{
    global $base_url, $api_token;

    $url = $base_url . "/apirest.php/UserEmail";

    //echo $url;
    //$url = $base_url . "/apirest.php/User/?range=1-1000&is_deleted=0";

    $headers = array(
        "Content-Type: application/json",
        "Session-Token: ".$sessionToken,
        "App-Token: " . $api_token
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['input' => $CorreosData]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if ($response === false) {
        return "Error al hacer la solicitud: " . curl_error($ch);
    } else {
        return $response; // La respuesta es un JSON con el token de sesión
    }

    curl_close($ch);
}

//Logs
function CrearArchivoLog($file_name)
{

    if (!file_exists($file_name)) {
        $file = fopen($file_name, 'w'); // Abre el archivo en modo escritura
        fclose($file); // Cierra el archivo
        return 'Log Creado';

        if ($file === false) {
            die("No se pudo crear el archivo de registro.");
        } else {
            fclose($file); // Cierra el archivo
        }
    }


}

function InsertarEntradaLog($file_name, $message)
{
    if (!file_exists($file_name)) {
        die("El archivo de registro no existe.");
    }

    $currentDateTime = date("Y-m-d H:i:s");
    $logMessage = $currentDateTime . ' - ' . $message . PHP_EOL;

    if (file_put_contents($file_name, $logMessage, FILE_APPEND | LOCK_EX) === false) {
        die("No se pudo escribir en el archivo de registro.");
    }

}



?>