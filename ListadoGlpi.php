<Title>Listado Ldap</Title>
<?php
	include('inc/head.inc');
	include('tareas.php');
	$DataTokenSession = TraerSessionToken();

$jsonSessionArray = json_decode($DataTokenSession, true);

if ($jsonSessionArray !== null && isset($jsonSessionArray["session_token"])) 
{
	$sessionToken = $jsonSessionArray["session_token"];

	//2. Se trae los usuarios de GLPI

    $usuariosGlpi = TraerUsuariosGlpi($sessionToken);
    $jsonUsuariosGlpi = json_decode($usuariosGlpi, true);
?>
<div class="container">
	<br>
	<h1 class="text-center">Listado GLPI </h1>
	<br>
	<table class="table table-bordered">
		<thead>
			<tr>
				<th>Id</th>
				<th>Nombre </th>
				<th>Estado</th>
			</tr>
		</thead>
		<tbody>

				<?php
	 				if (!empty($jsonUsuariosGlpi)) 
	    			{
	        			foreach ($jsonUsuariosGlpi as $UsuarioGlpi) 
	        			{            
	            			 
            				$IdUsuarioGlpi = $UsuarioGlpi['id'];
            				$NombreCompletoGlpi = $UsuarioGlpi['name'];
            				$estadoGlpi =  $UsuarioGlpi['is_active'];
            				$activoReaL;

            				switch ($estadoGlpi) 
            				{
            					case 0:
            						$activoReaL = "No";
            						break;
            					case 1:
            						$activoReaL = "Si";
            						break;
            				}



		            			echo "<tr>";
		            			echo "<td>".$IdUsuarioGlpi."</td>";
		            			echo "<td>".$NombreCompletoGlpi."</td>";
		            			echo "<td>".$activoReaL."</td>";
		            			echo "</tr>";
						}     
	    			} 
				?>
		</tbody>
</table>
</div>
<?php
$CerrarSesion = CerrarSessionToken($sessionToken);

}
else 
{
    $error = "No es pósible traer un token de sesión para esta ejecucíon: " . $sessionData.". <br> La ejecucíon no pudo ser realizada.";

    echo  $error.'<br>';
}
?>
	
<?php
	
	include('inc/foot.inc');
?>
