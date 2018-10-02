<?php
class Usuario {
	//estados usuario
	const USR_INACTIVO=0;
	const USR_ACTIVO=1;
	const USR_PENDIENTE=2;
	const USR_ELIMINADO=9;
	//estados empresa
	const EMP_INACTIVA=0;
	const EMP_ACTIVA=1;
	const EMP_ELIMINADA=9;
	
	//Centros.estado, Dispositivos.estado, Sensores.estado, Alarmass.situacion
	const ESTADO_ELIMINADO=0;
	const ESTADO_ACTIVO=1;
	const ESTADO_INACTIVO=9;
	//Usuarios.tipo
	const TIPO_MAESTRO=9;
	const TIPO_ADMIN=1;
	//const TIPO_PLATAFORMA=1;	//a extinguir!!!
	const TIPO_EMPRESA=2;
	const TIPO_CENTRO=3;
	const TIPO_DISPOSITIVO=4;
	//Usuarios.bits_panel
	const BIT_CENTROS=0;
	const BIT_DISPOSITIVOS=1;
	const BIT_SENSORES=2;
	const BIT_ALARMAS=3;
	const BIT_MAPA_CENTROS=4;	//obsoleto
	const BIT_CHAT=5;
	//Sensores.id_parametro
	const PARAMETRO_ANALOGICO=1;
	const PARAMETRO_DIGITAL=2;
	const PARAMETRO_TRIESTADO=3;
	//Alarmass.estado
	const ALARMA_OFF=0;		//alarma inactiva
	const ALARMA_ON=1;		//alarma activa
	const ALARMA_ACK=2;		//alarma activa y reconocida
	const ALARMA_ON_PROVISIONAL=3;
	
	
	
	
	public  $id;	//			=0;
	public  $email;	//			="?";
	private $clave;	//			="?";
	private $clave_encrip;	//  ="?";
	public  $tipo;	//			=self::TIPO_DISPOSITIVO;
	public  $id_empresa;	//	=0;
	public  $empresa;	//		="";
	public  $imagen_empresa;	//	="";
	public  $fecha_alta;	//	="";
	public  $imagen;	//	    ="";
	public  $bits_panel;	//  =0;	//pow(2, self::BIT_CENTROS)+pow(2,self::BIT_MAPA_CENTROS)+pow(2,self::BIT_ALARMAS)+pow(2,self::BIT_CHAT)+pow(2,self::TIPO_DISPOSITIVO)+pow(2,self::BIT_SENSORES);
	public  $lista_panel;	//  =self::BIT_CENTROS.",".self::BIT_MAPA_CENTROS.",".self::BIT_ALARMAS.",".self::BIT_CHAT.",".self::TIPO_DISPOSITIVO.",".self::BIT_SENSORES;
	public  $estado;	//		=self::ESTADO_INACTIVO;
	public $idioma;
	public $token;
	
	function __construct($email, $clave) {
		$this->email=$email;
		$this->clave=$clave;
		/*$this->id=0;
		$this->email="?";
		$this->clave="?";
		$this->clave_encrip="?";
		$this->tipo=self::TIPO_DISPOSITIVO;
		$this->id_empresa=0;
		$this->empresa="";
		$this->imagen_empresa="";
		$this->fecha_alta="";
		$this->imagen="";
		$this->bits_panel=0;	//pow(2, self::BIT_CENTROS)+pow(2,self::BIT_MAPA_CENTROS)+pow(2,self::BIT_ALARMAS)+pow(2,self::BIT_CHAT)+pow(2,self::TIPO_DISPOSITIVO)+pow(2,self::BIT_SENSORES);
		$this->lista_panel=self::BIT_CENTROS.",".self::BIT_MAPA_CENTROS.",".self::BIT_ALARMAS.",".self::BIT_CHAT.",".self::TIPO_DISPOSITIVO.",".self::BIT_SENSORES;
		$this->estado=self::ESTADO_INACTIVO;*/
	}
	
	function GetFromEmail() {
		//solo busca los usuarios activos (estado=1)
		//devuelve: -1:error (formato email o sql), 0:no existe, $array:datos usuario
		
		$email=filter_var($this->email, FILTER_VALIDATE_EMAIL);
		if ($email===false) return -2;	//formato email incorrecto
		$query="SELECT id_usuario, email, clave, alta, tipo, Usuarios.id_empresa, Usuarios.imagen as imagen_usr, bits_panel, lista_panel, Usuarios.estado, token,
					empresa, Empresas.imagen as imagen_emp
				FROM Usuarios 
				INNER JOIN Empresas ON Empresas.id_empresa=Usuarios.id_empresa
				WHERE email='".$email."' LIMIT 1";
		//echo $query;
		require('conexionBD.php');
		if (!$result=@mysqli_query($dbcon, $query)) return -1;	//error sql
		elseif (mysqli_num_rows($result)<=0) $return=0;			//email no encontrado
		else {
			$row=mysqli_fetch_array($result, MYSQLI_ASSOC);
			$this->id=$row['id_usuario'];
			$this->clave_encrip=$row['clave'];
			$this->tipo=$row['tipo'];
			$this->id_empresa=$row['id_empresa'];
			$this->empresa=$row['empresa'];
			$this->imagen_empresa=$row['imagen_emp'];
			$this->fecha_alta=$row['alta'];
			$this->imagen=$row['imagen_usr'];
			$this->bits_panel=$row['bits_panel'];
			$this->lista_panel=$row['lista_panel'];
			$this->estado=$row['estado'];
			$this->token=$row['token'];
			$return=1;
		}
		mysqli_close($dbcon);
		return ($return);
	}
	
	function ValidateClave() {
		if (sha1($this->clave)==$this->clave_encrip) return true;
		else return false;
	}
	
	function CentrosEmpresa() {
		$query="SELECT id_centro, centro, direccion, latitud, longitud, imagen
			FROM Centros 
			WHERE estado!=".self::ESTADO_ELIMINADO." AND id_empresa=".$this->id_empresa."
			ORDER BY centro";
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	
	function Centros() {
		//lista centros activos
		if ($this->tipo==self::TIPO_MAESTRO or $this->tipo==self::TIPO_ADMIN)
			$query="SELECT id_centro, centro, direccion, id_pais, latitud, longitud, imagen, estado
				FROM Centros 
				WHERE estado=".self::ESTADO_ACTIVO." 
				ORDER BY centro";
		elseif ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT id_centro, centro, direccion, id_pais, latitud, longitud, imagen, estado
				FROM Centros 
				WHERE estado=".self::ESTADO_ACTIVO." AND id_empresa=".$this->id_empresa."
				ORDER BY centro";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Centros.id_centro, centro, direccion, id_pais, latitud, longitud, imagen, estado
				FROM Centros
				INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Centros.id_centro
				WHERE Centros.estado=".self::ESTADO_ACTIVO." AND id_usuario=".$this->id."
				ORDER BY centro";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Centros.id_centro, centro, direccion, id_pais, latitud, longitud, imagen, estado
				FROM Centros
				INNER JOIN Dispositivos ON Dispositivos.id_centro=Centros.id_centro
				INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
				WHERE Centros.estado=".self::ESTADO_ACTIVO." AND id_usuario=".$this->id."
				ORDER BY centro";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	function CentrosConfig() {
		//lista centros activos e inactivos
		if ($this->tipo==self::TIPO_MAESTRO or $this->tipo==self::TIPO_ADMIN)
			$query="SELECT Empresas.id_empresa, Empresas.imagen as imagen_empresa, empresa, id_centro, centro, direccion, id_pais, latitud, longitud, Centros.imagen, Centros.estado
				FROM Centros 
				INNER JOIN Empresas ON Empresas.id_empresa=Centros.id_empresa
				WHERE Centros.estado!=".self::ESTADO_ELIMINADO."
				ORDER BY Empresas.id_empresa, centro";
		elseif ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT id_empresa, id_centro, centro, direccion, id_pais, latitud, longitud, imagen, estado
				FROM Centros 
				WHERE estado!=".self::ESTADO_ELIMINADO." AND id_empresa=".$this->id_empresa."
				ORDER BY centro";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Centros.id_empresa, Centros.id_centro, centro, direccion, id_pais, latitud, longitud, Centros.imagen, Centros.estado
				FROM Centros
				INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Centros.id_centro
				WHERE Centros.estado!=".self::ESTADO_ELIMINADO." AND id_usuario=".$this->id."
				ORDER BY centro";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Centros.id_empresa, Centros.id_centro, centro, direccion, id_pais, latitud, longitud, Centros.imagen, Centros.estado
				FROM Centros
				INNER JOIN Dispositivos ON Dispositivos.id_centro=Centros.id_centro
				INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
				WHERE Centros.estado!=".self::ESTADO_ELIMINADO." AND id_usuario=".$this->id."
				ORDER BY centro";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	function CentrosAlarmasOn($id_centro=0) {
		//lista centros y numero de alarmas ON
		
		if ($id_centro!=0) $filtro="Centros.id_centro=".$id_centro." AND ";
		else $filtro="";
		
		if ($this->tipo==self::TIPO_MAESTRO or $this->tipo==self::TIPO_ADMIN)
			$query="SELECT Centros.id_centro, centro, Centros.imagen, Centros.latitud, Centros.longitud, COUNT(Alarmass.estado) AS alarmas_on
				FROM Centros 
				LEFT JOIN Dispositivos ON Dispositivos.id_centro=Centros.id_centro AND Dispositivos.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Centros.estado=".self::ESTADO_ACTIVO."
				GROUP BY Centros.id_centro, centro, Centros.imagen, Centros.latitud, Centros.longitud
				ORDER BY id_empresa, alarmas_on DESC, centro";
		elseif ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT Centros.id_centro, centro, Centros.imagen, Centros.latitud, Centros.longitud, COUNT(Alarmass.estado) AS alarmas_on
				FROM Centros 
				LEFT JOIN Dispositivos ON Dispositivos.id_centro=Centros.id_centro AND Dispositivos.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Centros.estado=".self::ESTADO_ACTIVO." AND 
					  id_empresa=".$this->id_empresa."
				GROUP BY Centros.id_centro, centro, Centros.imagen, Centros.latitud, Centros.longitud
				ORDER BY alarmas_on DESC, centro";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Centros.id_centro, centro, direccion, Centros.latitud, Centros.longitud, Centros.imagen, COUNT(Alarmass.estado) AS alarmas_on
				FROM Centros
				INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Centros.id_centro
				LEFT JOIN Dispositivos ON Dispositivos.id_centro=Centros.id_centro AND Dispositivos.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Centros.estado=".self::ESTADO_ACTIVO." AND 
					  id_usuario=".$this->id."
				GROUP BY Centros.id_centro, centro, direccion, Centros.latitud, Centros.longitud, Centros.imagen
				ORDER BY centro";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Centros.id_centro, centro, direccion, Centros.latitud, Centros.longitud, Centros.imagen, COUNT(Alarmass.estado) AS alarmas_on
				FROM Centros
				INNER JOIN Dispositivos ON Dispositivos.id_centro=Centros.id_centro AND Dispositivos.estado=".self::ESTADO_ACTIVO."
				INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro." Centros.estado=".self::ESTADO_ACTIVO." AND 
					   id_usuario=".$this->id."
				GROUP BY Centros.id_centro, centro, direccion, Centros.latitud, Centros.longitud, Centros.imagen
				ORDER BY centro";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	
	function UsuariosCentros($id_centro) {
		$query="SELECT Usuarios.id_usuario, email, Usuarios.imagen
				FROM UsuariosCentros
				INNER JOIN Usuarios ON Usuarios.id_usuario=UsuariosCentros.id_usuario
				WHERE Usuarios.estado=1 AND id_centro=".$id_centro."
				ORDER BY email";
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	
	function DispositivosCentro($id_centro) {
		$query="SELECT id_dispositivo, dispositivo
			FROM Dispositivos 
			WHERE id_centro=".$id_centro."
			ORDER BY dispositivo";
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	function DispositivosEmpresa() {
		$query="SELECT Centros.id_centro, centro, Centros.imagen, id_dispositivo, dispositivo
				FROM Dispositivos
				INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro
				INNER JOIN Empresas ON Empresas.id_empresa=Centros.id_empresa
				WHERE Empresas.id_empresa=".$this->id_empresa."
				ORDER BY centro, dispositivo";
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			//$result=array();
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[$row['id_centro']][]=$row;	//$return[]=$row;
			
		}
		mysqli_close($dbcon);
		return $return;
	}
	function Dispositivos() {
		//lsita dispositivos activos
		if ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT Centros.id_centro, centro, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, Dispositivos.estado
					FROM Dispositivos
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Empresas ON Empresas.id_empresa=Centros.id_empresa
					WHERE Dispositivos.estado=".self::ESTADO_ACTIVO." AND Empresas.id_empresa=".$this->id_empresa."
					ORDER BY centro, dispositivo";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Centros.id_centro, centro, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, Dispositivos.estado
					FROM Dispositivos
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					WHERE Dispositivos.estado=".self::ESTADO_ACTIVO." AND UsuariosCentros.id_usuario=".$this->id."
					ORDER BY centro, dispositivo";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Centros.id_centro, centro, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, Dispositivos.estado
					FROM Dispositivos
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
					WHERE Dispositivos.estado=".self::ESTADO_ACTIVO." AND UsuariosDispositivos.id_usuario=".$this->id."
					ORDER BY centro, dispositivo";
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			//$result=array();
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;	//$return[]=$row;
			
		}
		mysqli_close($dbcon);
		return $return;
	}
	function DispositivosConfig() {
		//lsita dispositivos activos e inactivos
		if ($this->tipo==self::TIPO_MAESTRO or $this->tipo==self::TIPO_ADMIN)
			$query="SELECT Empresas.id_empresa, empresa, 
						   Centros.id_centro, centro, Centros.imagen as imagen_centro, Centros.estado as estado_centro, 
						   Dispositivos.id_dispositivo, dispositivo, Dispositivos.imagen as imagen_dispositivo, ubicacion, Dispositivos.latitud, Dispositivos.longitud, altura, Dispositivos.estado as estado_dispositivo
					FROM Dispositivos
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Empresas ON Empresas.id_empresa=Centros.id_empresa
					WHERE Dispositivos.estado!=".self::ESTADO_ELIMINADO."
					ORDER BY Empresas.id_empresa, centro, dispositivo";
		elseif ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT Empresas.id_empresa, 
						Centros.id_centro, centro, Centros.imagen as imagen_centro, Centros.estado as estado_centro, 
						Dispositivos.id_dispositivo, dispositivo, Dispositivos.imagen as imagen_dispositivo, ubicacion, Dispositivos.latitud, Dispositivos.longitud, altura, Dispositivos.estado as estado_dispositivo
					FROM Dispositivos
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Empresas ON Empresas.id_empresa=Centros.id_empresa
					WHERE Dispositivos.estado!=".self::ESTADO_ELIMINADO." AND Empresas.id_empresa=".$this->id_empresa."
					ORDER BY centro, dispositivo";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Centros.id_empresa, Centros.id_centro, centro, Centros.imagen as imagen_centro, Centros.estado as estado_centro, 
							Dispositivos.id_dispositivo, dispositivo, Dispositivos.imagen as imagen_dispositivo, ubicacion, Dispositivos.latitud, Dispositivos.longitud, altura, Dispositivos.estado as estado_dispositivo
					FROM Dispositivos
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					WHERE Dispositivos.estado!=".self::ESTADO_ELIMINADO." AND UsuariosCentros.id_usuario=".$this->id."
					ORDER BY centro, dispositivo";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Centros.id_empresa, Centros.id_centro, centro, Centros.imagen as imagen_centro, Centros.estado as estado_centro, 
						Dispositivos.id_dispositivo, dispositivo, Dispositivos.imagen as imagen_dispositivo, ubicacion, Dispositivos.latitud, Dispositivos.longitud, altura, Dispositivos.estado as estado_dispositivo
					FROM Dispositivos
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
					WHERE Dispositivos.estado!=".self::ESTADO_ELIMINADO." AND UsuariosDispositivos.id_usuario=".$this->id."
					ORDER BY centro, dispositivo";
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			//$result=array();
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;	//$return[]=$row;
			
		}
		mysqli_close($dbcon);
		return $return;
	}
	function DispositivosAlarmasOn($id_centro=0, $id_dispositivo=0) {
		if ($id_dispositivo!=0) $filtro="Dispositivos.id_dispositivo=".$id_dispositivo." AND ";
		elseif ($id_centro!=0) $filtro="Centros.id_centro=".$id_centro." AND ";
		else $filtro="";
		if ($this->tipo==self::TIPO_MAESTRO or $this->tipo==self::TIPO_ADMIN)
			$query="SELECT Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo, COUNT(Alarmass.estado) AS alarmas_on
				FROM Dispositivos 
				INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Dispositivos.estado=".self::ESTADO_ACTIVO."
				GROUP BY Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo
				ORDER BY id_empresa, alarmas_on DESC, centro, dispositivo";
		elseif ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo, COUNT(Alarmass.estado) AS alarmas_on
				FROM Dispositivos 
				INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Dispositivos.estado=".self::ESTADO_ACTIVO." AND Centros.id_empresa=".$this->id_empresa."
				GROUP BY Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo
				ORDER BY alarmas_on DESC, centro, dispositivo";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo, COUNT(Alarmass.estado) AS alarmas_on
				FROM Dispositivos
				INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
				INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Centros.id_centro
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Dispositivos.estado=".self::ESTADO_ACTIVO." AND UsuariosCentros.id_usuario=".$this->id."
				GROUP BY Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo
				ORDER BY centro, dispositivo";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo, COUNT(Alarmass.estado) AS alarmas_on
				FROM Dispositivos
				INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
				INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Dispositivos.estado=".self::ESTADO_ACTIVO." AND UsuariosDispositivos.id_usuario=".$this->id."
				GROUP BY Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo
				ORDER BY centro, dispositivo";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	
	function DispositivosAlarmas($id_centro=0, $id_dispositivo=0) {
		if ($id_dispositivo!=0) $filtro="Dispositivos.id_dispositivo=".$id_dispositivo." AND ";
		elseif ($id_centro!=0) $filtro="Centros.id_centro=".$id_centro." AND ";
		else $filtro="";
		if ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT Centros.id_centro, centro, Centros.latitud, Centros.longitud, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, COUNT(Alarmass.estado) AS alarmas_on
				FROM Centros 
				LEFT JOIN Dispositivos ON Centros.id_centro=Dispositivos.id_centro AND Dispositivos.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Centros.estado=".self::ESTADO_ACTIVO." AND Centros.id_empresa=".$this->id_empresa."
				GROUP BY Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo
				ORDER BY alarmas_on DESC, centro, dispositivo";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Centros.id_centro, centro,Centros.latitud, Centros.longitud, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, COUNT(Alarmass.estado) AS alarmas_on
				FROM Centros
				INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Centros.id_centro
				LEFT JOIN Dispositivos ON Centros.id_centro=Dispositivos.id_centro AND Dispositivos.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Centros.estado=".self::ESTADO_ACTIVO." AND UsuariosCentros.id_usuario=".$this->id."
				GROUP BY Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo
				ORDER BY centro, dispositivo";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Centros.id_centro, centro, Centros.latitud, Centros.longitud, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, COUNT(Alarmass.estado) AS alarmas_on
				FROM Centros
				INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
				LEFT JOIN Dispositivos ON Centros.id_centro=Dispositivos.id_centro AND Dispositivos.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Sensores ON Sensores.id_dispositivo=Dispositivos.id_dispositivo AND Sensores.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Centros.estado=".self::ESTADO_ACTIVO." AND UsuariosDispositivos.id_usuario=".$this->id."
				GROUP BY Centros.id_centro, centro, Dispositivos.id_dispositivo, dispositivo
				ORDER BY centro, dispositivo";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	
	function Sensores() {
		if ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT Centros.id_centro, centro, Centros.imagen as imagen_centro, Dispositivos.id_dispositivo, dispositivo, Sensores.id_sensor, sensor, Sensores.imagen as imagen_sensor, unidades, Sensores.estado
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					WHERE Sensores.estado=".self::ESTADO_ACTIVO." AND Centros.id_empresa=".$this->id_empresa."
					ORDER BY centro, dispositivo, sensor";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Centros.id_centro, centro, Centros.imagen as imagen_centro, Dispositivos.id_dispositivo, dispositivo, Sensores.id_sensor, sensor, Sensores.imagen as imagen_sensor, unidades, Sensores.estado
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					WHERE Sensores.estado=".self::ESTADO_ACTIVO." AND UsuariosCentros.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Centros.id_centro, centro, Centros.imagen as imagen_centro, Dispositivos.id_dispositivo, dispositivo, Sensores.id_sensor, sensor, Sensores.imagen as imagen_sensor, unidades, Sensores.estado
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO." 
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
					WHERE Sensores.estado=".self::ESTADO_ACTIVO." AND UsuariosDispositivos.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	function SensoresConfig() {
		if ($this->tipo==self::TIPO_MAESTRO or $this->tipo==self::TIPO_ADMIN)
			$query="SELECT Centros.id_centro, centro, Centros.imagen as imagen_centro, Centros.estado as estado_centro, 
						Dispositivos.id_dispositivo, dispositivo, Dispositivos.estado as estado_dispositivo, 
						Sensores.id_sensor, sensor, Sensores.imagen as imagen_sensor, unidades, Sensores.estado as estado_sensor, id_parametro
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					WHERE Sensores.estado!=".self::ESTADO_ELIMINADO." 
					ORDER BY id_empresa, centro, dispositivo, sensor";
		elseif ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT Centros.id_centro, centro, Centros.imagen as imagen_centro, Centros.estado as estado_centro, 
						Dispositivos.id_dispositivo, dispositivo, Dispositivos.estado as estado_dispositivo, 
						Sensores.id_sensor, sensor, Sensores.imagen as imagen_sensor, unidades, Sensores.estado as estado_sensor, id_parametro
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					WHERE Sensores.estado!=".self::ESTADO_ELIMINADO." AND Centros.id_empresa=".$this->id_empresa."
					ORDER BY centro, dispositivo, sensor";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Centros.id_centro, centro, Centros.imagen as imagen_centro, Centros.estado as estado_centro, 
						Dispositivos.id_dispositivo, dispositivo, Dispositivos.estado as estado_dispositivo, 
						Sensores.id_sensor, sensor, Sensores.imagen as imagen_sensor, unidades, Sensores.estado as estado_sensor, id_parametro
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					WHERE Sensores.estado!=".self::ESTADO_ELIMINADO." AND UsuariosCentros.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Centros.id_centro, centro, Centros.imagen as imagen_centro, Centros.estado as estado_centro, 
						Dispositivos.id_dispositivo, dispositivo, Dispositivos.estado as estado_dispositivo, 
						Sensores.id_sensor, sensor, Sensores.imagen as imagen_sensor, unidades, Sensores.estado as estado_sensor, id_parametro
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado!=".self::ESTADO_ELIMINADO." 
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
					WHERE Sensores.estado!=".self::ESTADO_ELIMINADO." AND UsuariosDispositivos.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	function SensoresDispositivo($id_dispositivo) {
		$query="SELECT Sensores.id_sensor, sensor, imagen
			FROM Sensores
			WHERE id_dispositivo=".$id_dispositivo."
			ORDER BY sensor";
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	function SensoresAlarmasOn($id_centro=0, $id_dispositivo=0, $id_sensor=0) {
		if ($id_sensor!=0) $filtro="Sensores.id_sensor=".$id_sensor." AND ";
		elseif ($id_dispositivo!=0) $filtro="Dispositivos.id_dispositivo=".$id_dispositivo." AND ";
		elseif ($id_centro!=0) $filtro="Centros.id_centro=".$id_centro." AND ";
		else $filtro="";
		if ($this->tipo==self::TIPO_ADMIN or $this->tipo==self::TIPO_MAESTRO)
			$query=$query="SELECT Centros.id_centro, centro, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, Sensores.id_sensor, sensor, COUNT(Alarmass.estado) AS alarmas_on
				FROM Sensores
				INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
				INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Sensores.estado=".self::ESTADO_ACTIVO."
				GROUP BY Centros.id_centro, centro, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, Sensores.id_sensor, sensor
				ORDER BY id_empresa, alarmas_on DESC, centro, dispositivo, sensor";
		elseif ($this->tipo==self::TIPO_EMPRESA) {
			$query=$query="SELECT Centros.id_centro, centro, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, Sensores.id_sensor, sensor, COUNT(Alarmass.estado) AS alarmas_on
				FROM Sensores
				INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
				INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
				LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
				WHERE ".$filtro."Sensores.estado=".self::ESTADO_ACTIVO." AND Centros.id_empresa=".$this->id_empresa."
				GROUP BY Centros.id_centro, centro, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, Sensores.id_sensor, sensor
				ORDER BY alarmas_on DESC, centro, dispositivo, sensor";
		}
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Centros.id_centro, centro, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, Sensores.id_sensor, sensor, COUNT(Alarmass.estado) AS alarmas_on
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Centros.id_centro
					LEFT JOIN Alarmass ON Alarmass.id_sensor=Sensores.id_sensor AND (Alarmass.estado=".self::ALARMA_ON." OR (Alarmass.estado=".self::ALARMA_ON_PROVISIONAL." AND Alarmass.activacion<='".date('Y-m-d H:i:s')."')) AND Alarmass.situacion=".self::ESTADO_ACTIVO."
					WHERE ".$filtro."Sensores.estado=".self::ESTADO_ACTIVO." AND id_usuario=".$this->id."
					GROUP BY Centros.id_centro, centro, Centros.imagen, Dispositivos.id_dispositivo, dispositivo, Sensores.id_sensor, sensor
					ORDER BY centro, dispositivo, sensor";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	
	
	
	
	function SensoresUltimaMedida($id_centro=0, $id_dispositivo=0, $id_sensor=0) {
		if ($id_sensor!=0) $filtro="Sensores.id_sensor=".$id_sensor." AND ";
		elseif ($id_dispositivo!=0) $filtro="Dispositivos.id_dispositivo=".$id_dispositivo." AND ";
		elseif ($id_centro!=0) $filtro="Centros.id_centro=".$id_centro." AND ";
		else $filtro="";
		if ($this->tipo==self::TIPO_MAESTRO or $this->tipo==self::TIPO_ADMIN)
			$query="SELECT Sensores.id_sensor, sensor, unidades,
						empresa, centro, 
						dispositivo,
						Medidas.valor, Medidas.fecha as fecha_medida,
						UsuariosPanel2.id_usuario
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Empresas ON Empresas.id_empresa=Centros.id_empresa 
					LEFT JOIN (select id_sensor, max(fecha) as fecha_max from Medidas group by id_sensor) as Tmp ON Tmp.id_sensor=Sensores.id_sensor
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor and Medidas.fecha=Tmp.fecha_max
					left join UsuariosPanel2 on UsuariosPanel2.id_sensor=Sensores.id_sensor and UsuariosPanel2.id_usuario=".$this->id."
					WHERE ".$filtro."Sensores.estado=".self::ESTADO_ACTIVO."
					ORDER BY empresa,centro, dispositivo, sensor
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT Sensores.id_sensor, sensor, unidades,
						centro, 
						dispositivo,
						Medidas.valor, Medidas.fecha as fecha_medida,
						UsuariosPanel2.id_usuario
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					LEFT JOIN (select id_sensor, max(fecha) as fecha_max from Medidas group by id_sensor) as Tmp ON Tmp.id_sensor=Sensores.id_sensor
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor and Medidas.fecha=Tmp.fecha_max
					left join UsuariosPanel2 on UsuariosPanel2.id_sensor=Sensores.id_sensor and UsuariosPanel2.id_usuario=".$this->id."
					WHERE ".$filtro."Centros.id_empresa=".$this->id_empresa."
					ORDER BY centro, dispositivo, sensor 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Sensores.id_sensor, sensor, unidades,
						centro, 
						dispositivo,
						Medidas.valor, Medidas.fecha as fecha_medida,
						UsuariosPanel2.id_usuario
					FROM Sensores
					INNER JOIN Sensores on Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					LEFT JOIN (select id_sensor, max(fecha) as fecha_max from Medidas group by id_sensor) as Tmp ON Tmp.id_sensor=Sensores.id_sensor
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor and Medidas.fecha=Tmp.fecha_max
					left join UsuariosPanel2 on UsuariosPanel2.id_sensor=Sensores.id_sensor and UsuariosPanel2.id_usuario=".$this->id."
					WHERE ".$filtro."UsuariosCentros.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Sensores.id_sensor, sensor, unidades,
						centro, 
						dispositivo,
						Medidas.valor, Medidas.fecha as fecha_medida,
						UsuariosPanel2.id_usuario
					FROM Sensores
					INNER JOIN Sensores on Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
					LEFT JOIN (select id_sensor, max(fecha) as fecha_max from Medidas group by id_sensor) as Tmp ON Tmp.id_sensor=Sensores.id_sensor
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor and Medidas.fecha=Tmp.fecha_max
					left join UsuariosPanel2 on UsuariosPanel2.id_sensor=Sensores.id_sensor and UsuariosPanel2.id_usuario=".$this->id."
					WHERE ".$filtro."UsuariosDispositivos.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor
					LIMIT 50";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	
	
	
	
	
	function SensoresOcultos() {
		//sensores que el usuario ha ocultado del panel 2
		$query="select empresa, centro, dispositivo, Sensores.id_sensor, sensor 
			from UsuariosPanel2
			INNER JOIN Sensores ON Sensores.id_sensor=UsuariosPanel2.id_sensor and id_usuario=".$this->id."
			INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo 
			INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro 
			INNER JOIN Empresas ON Empresas.id_empresa=Centros.id_empresa
			ORDER BY empresa,centro,dispositivo,sensor";
		require('conexionBD.php');
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	
	
	
	
	function AlarmasMedidas($id_centro=0, $id_dispositivo=0, $id_sensor=0) {
		if ($id_sensor!=0) $filtro="Sensores.id_sensor=".$id_sensor." AND ";
		elseif ($id_dispositivo!=0) $filtro="Dispositivos.id_dispositivo=".$id_dispositivo." AND ";
		elseif ($id_centro!=0) $filtro="Centros.id_centro=".$id_centro." AND ";
		else $filtro="";
		if ($this->tipo==self::TIPO_MAESTRO or $this->tipo==self::TIPO_ADMIN)
			/*$query="SELECT DISTINCT(Alarmass.id_alarma), alarma, valor_min, valor_max, condicion, offset, duracion, unidades, delay, activacion, Alarmass.estado, Alarmass.fecha as fecha_alarma,
						centro, 
						dispositivo, 
						Sensores.id_sensor, sensor,
						Medidas.valor, Medidas.fecha as fecha_medida
					FROM Alarmass
					INNER JOIN Sensores on Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					LEFT JOIN (select id_sensor, max(fecha) as fecha_max from Medidas group by id_sensor) as Tmp ON Tmp.id_sensor=Sensores.id_sensor
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor and Medidas.fecha=Tmp.fecha_max
					WHERE ".$filtro."Alarmass.situacion=".self::ESTADO_ACTIVO."
					ORDER BY id_empresa,centro, dispositivo, sensor, alarma 
					LIMIT 50";*/
				
			$query="SELECT Sensores.id_sensor, sensor, 
						centro, 
						dispositivo,
						Medidas.valor, Medidas.fecha as fecha_medida,
						Alarmass.id_alarma, alarma, valor_min, valor_max, condicion, offset, duracion, unidades, delay, activacion, Alarmass.estado, Alarmass.fecha as fecha_alarma
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					LEFT JOIN Alarmass ON Sensores.id_sensor=Alarmass.id_sensor AND Alarmass.situacion=".self::ESTADO_ACTIVO."
					LEFT JOIN (select id_sensor, max(fecha) as fecha_max from Medidas group by id_sensor) as Tmp ON Tmp.id_sensor=Sensores.id_sensor
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor and Medidas.fecha=Tmp.fecha_max
					WHERE ".$filtro."Sensores.estado=".self::ESTADO_ACTIVO."
					ORDER BY id_empresa,centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT Sensores.id_sensor, sensor, 
						centro, 
						dispositivo,
						Medidas.valor, Medidas.fecha as fecha_medida,
						Alarmass.id_alarma, alarma, valor_min, valor_max, condicion, offset, duracion, unidades, delay, activacion, Alarmass.estado, Alarmass.fecha as fecha_alarma
					FROM Sensores
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					LEFT JOIN Alarmass ON Sensores.id_sensor=Alarmass.id_sensor AND Alarmass.situacion=".self::ESTADO_ACTIVO."
					LEFT JOIN (select id_sensor, max(fecha) as fecha_max from Medidas group by id_sensor) as Tmp ON Tmp.id_sensor=Sensores.id_sensor
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor and Medidas.fecha=Tmp.fecha_max
					WHERE ".$filtro."Centros.id_empresa=".$this->id_empresa."
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT Sensores.id_sensor, sensor, 
						centro, 
						dispositivo,
						Medidas.valor, Medidas.fecha as fecha_medida,
						Alarmass.id_alarma, alarma, valor_min, valor_max, condicion, offset, duracion, unidades, delay, activacion, Alarmass.estado, Alarmass.fecha as fecha_alarma
					FROM Sensores
					INNER JOIN Sensores on Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					LEFT JOIN Alarmass ON Sensores.id_sensor=Alarmass.id_sensor AND Alarmass.situacion=".self::ESTADO_ACTIVO."
					LEFT JOIN (select id_sensor, max(fecha) as fecha_max from Medidas group by id_sensor) as Tmp ON Tmp.id_sensor=Sensores.id_sensor
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor and Medidas.fecha=Tmp.fecha_max
					WHERE ".$filtro."UsuariosCentros.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT Sensores.id_sensor, sensor, 
						centro, 
						dispositivo,
						Medidas.valor, Medidas.fecha as fecha_medida,
						Alarmass.id_alarma, alarma, valor_min, valor_max, condicion, offset, duracion, unidades, delay, activacion, Alarmass.estado, Alarmass.fecha as fecha_alarma
					FROM Sensores
					INNER JOIN Sensores on Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Dispositivos.id_dispositivo
					LEFT JOIN Alarmass ON Sensores.id_sensor=Alarmass.id_sensor AND Alarmass.situacion=".self::ESTADO_ACTIVO."
					LEFT JOIN (select id_sensor, max(fecha) as fecha_max from Medidas group by id_sensor) as Tmp ON Tmp.id_sensor=Sensores.id_sensor
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor and Medidas.fecha=Tmp.fecha_max
					WHERE ".$filtro."UsuariosDispositivos.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	function Alarmas() {
		if ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT centro, Centros.imagen, dispositivo, Sensores.id_sensor, sensor, id_alarma, alarma, Alarmass.estado, condicion, valor_min, valor_max, offset, Alarmass.fecha as fecha_alarma, Alarmass.situacion, unidades
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					WHERE Alarmass.situacion=".self::ESTADO_ACTIVO." AND Centros.id_empresa=".$this->id_empresa."
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT id_alarma, centro, dispositivo, sensor, alarma, condicion, valor_min, valor_max, offset, Alarmass.estado, Alarmass.fecha, situacion, MAX(Medidas.fecha) as ultimo, Medidas.valor
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor
					WHERE Alarmass.situacion=".self::ESTADO_ACTIVO." AND UsuariosCentros.id_usuario=".$this->id."
					GROUP BY Alarmass.id_sensor
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT id_alarma, centro, dispositivo, sensor, alarma, condicion, valor_min, valor_max, offset, Alarmass.estado, Alarmass.fecha, situacion, MAX(Medidas.fecha) as ultimo, Medidas.valor
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado=".self::ESTADO_ACTIVO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado=".self::ESTADO_ACTIVO."
					INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Sensores.id_dispositivo
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor
					WHERE Alarmass.situacion=".self::ESTADO_ACTIVO." AND UsuariosDispositivos.id_usuario=".$this->id."
					GROUP BY Alarmass.id_sensor
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	function AlarmasConfig() {
		if ($this->tipo==self::TIPO_MAESTRO or $this->tipo==self::TIPO_ADMIN)
			$query="SELECT centro, Centros.imagen, Centros.estado as estado_centro,
						dispositivo, Dispositivos.estado as estado_dispositivo,
						Sensores.id_sensor, sensor, Sensores.estado as estado_sensor,
						id_alarma, alarma, Alarmass.estado as estado_alarma, condicion, valor_min, valor_max, offset, Alarmass.fecha as fecha_alarma, Alarmass.situacion, unidades, delay
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					WHERE Alarmass.situacion!=".self::ESTADO_ELIMINADO." 
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT centro, Centros.imagen, Centros.estado as estado_centro,
						dispositivo, Dispositivos.estado as estado_dispositivo,
						Sensores.id_sensor, sensor, Sensores.estado as estado_sensor,
						id_alarma, alarma, Alarmass.estado as estado_alarma, condicion, valor_min, valor_max, offset, Alarmass.fecha as fecha_alarma, Alarmass.situacion, unidades, delay
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					WHERE Alarmass.situacion!=".self::ESTADO_ELIMINADO." AND Centros.id_empresa=".$this->id_empresa."
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT centro, Centros.imagen, Centros.estado as estado_centro,
						dispositivo, Dispositivos.estado as estado_dispositivo,
						Sensores.id_sensor, sensor, Sensores.estado as estado_sensor,
						id_alarma, alarma, Alarmass.estado as estado_alarma, condicion, valor_min, valor_max, offset, Alarmass.fecha as fecha_alarma, Alarmass.situacion, unidades, delay
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor
					WHERE Alarmass.situacion!=".self::ESTADO_ELIMINADO." AND UsuariosCentros.id_usuario=".$this->id."
					GROUP BY Alarmass.id_sensor
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT centro, Centros.imagen, Centros.estado as estado_centro,
						dispositivo, Dispositivos.estado as estado_dispositivo,
						Sensores.id_sensor, sensor, Sensores.estado as estado_sensor,
						id_alarma, alarma, Alarmass.estado as estado_alarma, condicion, valor_min, valor_max, offset, Alarmass.fecha as fecha_alarma, Alarmass.situacion, unidades, delay
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor AND Sensores.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo AND Dispositivos.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro AND Centros.estado!=".self::ESTADO_ELIMINADO."
					INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Sensores.id_dispositivo
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor
					WHERE Alarmass.situacion!=".self::ESTADO_ELIMINADO." AND UsuariosDispositivos.id_usuario=".$this->id."
					GROUP BY Alarmass.id_sensor
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	/*function Alarmas2() {
		if ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT centro, dispositivo, sensor, alarma, condicion, valor_min, valor_max, offset, duracion, Alarmass.estado, Alarmass.fecha, situacion
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro
					WHERE Centros.id_empresa=".$this->id_empresa."
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT centro, dispositivo, sensor, alarma, condicion, valor_min, valor_max, offset, duracion, Alarmass.estado, Alarmass.fecha, situacion
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					WHERE UsuariosCentros.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT centro, dispositivo, sensor, alarma, condicion, valor_min, valor_max, offset, duracion, Alarmass.estado, Alarmass.fecha, situacion
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor
					INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Sensores.id_dispositivo
					WHERE UsuariosDispositivos.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}*/
	/*function AlarmasOn() {
		if ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT centro, dispositivo, sensor, alarma, Alarmass.fecha as fecha_on, MAX(Medidas.fecha) as ultimo, Medidas.valor, condicion, valor_min, valor_max
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor
					WHERE Alarmass.estado!=0 AND Centros.id_empresa=".$this->id_empresa."
					GROUP BY Alarmass.id_sensor
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT centro, dispositivo, sensor, alarma, Alarmass.fecha as fecha_on, MAX(Medidas.fecha) as ultimo, Medidas.valor, condicion, valor_min, valor_max
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					LEFT JOIN Medidas ON Medidas.id_sensor=Sensores.id_sensor
					WHERE Alarmass.estado!=0 AND UsuariosCentros.id_usuario=".$this->id."
					GROUP BY Alarmass.id_sensor
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		elseif ($this->tipo==self::TIPO_DISPOSITIVO)
			$query="SELECT alarma, centro, fecha
					FROM Alarmass
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor
					INNER JOIN UsuariosDispositivos ON UsuariosDispositivos.id_dispositivo=Sensores.id_dispositivo
					WHERE Alarmass.estado!=0 AND UsuariosDispositivos.id_usuario=".$this->id."
					ORDER BY centro, dispositivo, sensor, alarma 
					LIMIT 50";
		else return 0;
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}*/
	
	function Notificaciones($id_centro=0, $id_dispositivo=0, $id_sensor=0) {
		if ($id_sensor!=0) $filtro="Sensores.id_sensor=".$id_sensor." AND ";
		elseif ($id_dispositivo!=0) $filtro="Dispositivos.id_dispositivo=".$id_dispositivo." AND ";
		elseif ($id_centro!=0) $filtro="Centros.id_centro=".$id_centro." AND ";
		else $filtro="";
		if ($this->tipo==self::TIPO_MAESTRO or $this->tipo==self::TIPO_ADMIN)
			$query="SELECT alarma, notificacion, Notificaciones.fecha 
					FROM Notificaciones
					INNER JOIN Alarmass ON Alarmass.id_alarma=Notificaciones.id_alarma
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro
					ORDER BY Notificaciones.fecha DESC
					LIMIT 10";
		elseif ($this->tipo==self::TIPO_EMPRESA)
			$query="SELECT alarma, notificacion, Notificaciones.fecha 
					FROM Notificaciones
					INNER JOIN Alarmass ON Alarmass.id_alarma=Notificaciones.id_alarma
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo
					INNER JOIN Centros ON Centros.id_centro=Dispositivos.id_centro
					WHERE ".$filtro."Centros.id_empresa=".$this->id_empresa."
					ORDER BY Notificaciones.fecha DESC
					LIMIT 10";
		elseif ($this->tipo==self::TIPO_CENTRO)
			$query="SELECT alarma, notificacion, Notificaciones.fecha
					FROM Notificaciones
					INNER JOIN Alarmass ON Alarmass.id_alarma=Notificaciones.id_alarma
					INNER JOIN Sensores ON Sensores.id_sensor=Alarmass.id_sensor
					INNER JOIN Dispositivos ON Dispositivos.id_dispositivo=Sensores.id_dispositivo
					INNER JOIN UsuariosCentros ON UsuariosCentros.id_centro=Dispositivos.id_centro
					WHERE ".$filtro."UsuariosCentros.id_usuario=".$this->id."
					ORDER BY Notificaciones.fecha DESC
					LIMIT 10";
		else $query="";
		//echo $query;
		require("conexionBD.php");
		if (!$result=@mysqli_query($dbcon, $query)) $return=-1;
		elseif (mysqli_num_rows($result)==0) $return=0;
		else {
			while ($row=mysqli_fetch_array($result, MYSQLI_ASSOC)) $return[]=$row;
		}
		mysqli_close($dbcon);
		return $return;
	}
	
	function TextoPanel($id_panel) {
		if (self::BIT_CENTROS==$id_panel) return "centros";
		elseif (self::BIT_DISPOSITIVOS==$id_panel) return "dispositivos";
		elseif (self::BIT_SENSORES==$id_panel) return "sensores";
		elseif (self::BIT_ALARMAS==$id_panel) return "alarmas";
		elseif (self::BIT_MAPA_CENTROS==$id_panel) return "mapa (obsoleto)";
		elseif (self::BIT_CHAT==$id_panel) return "chat";
		else return $id_panel.", panel texto???";
	}
	function TextoTipo() {
		if (self::TIPO_ADMIN==$this->tipo) return "admin";
		elseif (self::TIPO_EMPRESA==$this->tipo) return "empresa";
		elseif (self::TIPO_CENTRO==$this->tipo) return "centro";
		elseif (self::TIPO_DISPOSITIVO==$this->tipo) return "dispositivo";
		elseif (self::TIPO_MAESTRO==$this->tipo) return "maestro";
		else return $this->tipo.", tipo texto???";
	}
	function TextoTipoId($tipo) {
		if (self::TIPO_ADMIN==$tipo) return "admin";
		elseif (self::TIPO_EMPRESA==$tipo) return "empresa";
		elseif (self::TIPO_CENTRO==$tipo) return "centro";
		elseif (self::TIPO_DISPOSITIVO==$tipo) return "dispositivo";
		elseif (self::TIPO_MAESTRO==$tipo) return "maestro";
		else return "tipo texto ".$tipo."???";
	}
	function TextoEstado() {
		if (self::ESTADO_INACTIVO==$this->estado) return "disabled";
		elseif (self::ESTADO_ACTIVO==$this->estado) return "enabled";
		elseif (self::ESTADO_ELIMINADO==$this->estado) return "deleted";
		else return $this->estado.", estado texto???";
	}
}
?>