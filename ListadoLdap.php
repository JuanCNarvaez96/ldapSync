<Title>Listado Ldap</Title>
<?php
	include('inc/head.inc');
	include('tareas.php');
	$usuariosLdap = TraerUsuariosLdap();
?>
<div class="container">
	<br>
	<h1 class="text-center">Listado Ldap (Activos)</h1>
	<br>
	<table class="table table-bordered">
		<thead>
			<tr>
				<th>Nombre Usuario</th>
				<th>Nombre Completo</th>
				<th>Correo</th>
				<th>Guid</th>
				<th>BaseDN</th>
			</tr>
		</thead>
		<tbody>

				<?php
	 				if (!empty($usuariosLdap)) 
	    			{
	        			foreach ($usuariosLdap as $usuarioLdap) 
	        			{            
	            			$NombreUsuarioLdap = $usuarioLdap['Usuario'];
	            			$NombreCompletoLdap = $usuarioLdap['NombreComun'];
	            			$CorreoElectronicoLdap =  $usuarioLdap['CorreoElectronico'];
	           				$ObjectGuid =  $usuarioLdap['objectGUID'];
	            			$baseDn = $usuarioLdap['baseDn'];
	            			echo "<tr>";
	            			echo "<td>".$NombreUsuarioLdap."</td>";
	            			echo "<td>".$NombreCompletoLdap."</td>";
	            			echo "<td>".$CorreoElectronicoLdap."</td>";
	            			echo "<td>".$ObjectGuid."</td>";
	            			echo "<td>".$baseDn."</td>";
	            			echo "</tr>";
						}     
	    			} 
				?>
		</tbody>
</table>
</div>
<?php
	include('inc/foot.inc');
?>	

