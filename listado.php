<?php

// Configuración de conexión LDAP
$ldapServer = 'LDAP://10.192.1.26:389';
$ldapBaseDN = 'dc=coltrans,dc=local';
$ldapUser = 'iglpi@coltrans.local';
$ldapPass = 'vW2D!0J6&ab409bCpNAp';

// Conexión LDAP
$ldapConn = ldap_connect($ldapServer);
ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);

if ($ldapConn) {
    // Autenticación
     // Búsqueda de todos los atributos de un objeto LDAP (por ejemplo, un usuario)
        $filter = '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
        $attributes = array('*'); // Obtener todos los atributos

        $search = ldap_search($ldapConn, $ldapBaseDN, $filter, $attributes);
        $entries = ldap_get_entries($ldapConn, $search);

        // Mostrar resultados
        print_r($entries);

        // Cerrar conexión LDAP
        ldap_close($ldapConn);
    } else {
        echo 'Error al autenticar con el servidor LDAP.';
    }


?>






