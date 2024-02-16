<Title>Ejecutar Accion de sincronizacion</Title>
<?php
	include('inc/head.inc');
?>
<body>
<div class="container text-center">
	<br>
	<h4>Sincronizar Usuarios GLPI</h4>
	<br>
	<div class="container">
		<a href="sincronizarGlpi.php" class="btn btn-info">Ejecutar Sicronización usuarios</a>
	</div>
	<br>
	<div class="container">
		<a href="CorreoGlpi.php" class="btn btn-info">Ejecutar inclusión de correos</a>
	</div>
	<br>
	<div class="container">
		<a href="ListadoLdap.php" class="btn btn-info">Listado Ldap</a>
	</div>
	<br>
	<div class="container">
		<a href="ListadoGlpi.php" class="btn btn-info">Listado Glpi</a>
	</div>
</div>	
<br>
</body>

<?php
	include('inc/foot.inc');
?>