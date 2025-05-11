Project: andrei-lavenderblush
Version: 1.0
Author: Andrei

Descripción:
-------------
Este proyecto es una aplicación web multiusuario y multiproyecto diseñada para gestionar visualmente 
la creación, edición y almacenamiento de "clases" (estructuras o esquemas) a través de una interfaz 
de arrastrar y soltar. Además, permite exportar dichas clases a archivos PHP mediante el script "save_classes.php".

El sistema incluye:
  • Autenticación de usuarios (con un ejemplo de usuario predeterminado: username: andrei, password: andrei).
  • Creación y selección de proyectos.
  • Un área de trabajo interactiva donde se pueden añadir, mover, duplicar, eliminar y editar tarjetas 
    que representan clases.
  • Funcionalidad de auto-guardado de las clases (cada 30 segundos).
  • Búsqueda y filtrado de clases mediante un campo de texto.
  • Menú lateral plegable/desplegable con un botón que muestra una flecha para alternar el estado.

Estructura del Proyecto:
-------------------------
Raíz del proyecto:
  • data.sqlite         => Archivo SQLite que se crea en el primer uso y que almacena la información.
  • index.php           => Archivo principal que gestiona la interfaz, autenticación, proyectos, y 
                          comunicación con el servidor vía AJAX.
  • save_classes.php    => Script que recibe los datos en JSON de las clases y genera archivos PHP.

Carpetas:
  • css/                => Carpeta que contiene los archivos de estilos:
         - style.css   => Estilos generales (área principal, menús, elementos interactivos).
         - auth.css    => Estilos específicos para la pantalla de login y autenticación.
  • scripts/            => Carpeta que contiene los archivos JavaScript:
         - script.js   => Lógica del lado del cliente (interacción, arrastrar y soltar, auto-guardado, 
                          duplicar, eliminar elementos, búsqueda, etc.).

Requisitos:
-----------
  • PHP (con soporte para PDO y SQLite).
  • Un servidor web (por ejemplo, Apache con XAMPP o similar).
  • Navegador con JavaScript habilitado.

Instalación y Uso:
------------------
1. Clona o copia todos los archivos y carpetas en el directorio raíz de tu servidor web, por ejemplo:  
   C:\xampp\htdocs\andrei-lavenderblush\

2. Asegúrate de que el servidor tenga permisos de lectura/escritura en la carpeta para poder crear 
   y actualizar el archivo "data.sqlite".

3. Accede a la aplicación desde tu navegador apuntando a "index.php" (ej. http://localhost/andrei-lavenderblush/).

4. Inicia sesión utilizando las credenciales de demostración (username: andrei, password: andrei) o 
   crea un nuevo usuario según las opciones implementadas.

5. Utiliza las opciones del menú lateral para crear o seleccionar proyectos, y comienza a trabajar 
   con las clases en el área de trabajo. Controla las tarjetas:
     • Arrástralas para reposicionarlas.
     • Usa los botones "X" para eliminar y "D" para duplicar.
     • Escribe o edita el contenido de cada tarjeta.
     • Aprovecha la función de auto-guardado y el campo de búsqueda para gestionar tus clases.

Notas:
------
  • Este proyecto ha sido desarrollado como demostración/educativo. 
  • La autenticación se realiza con contraseñas en texto plano, por lo que no es adecuado para entornos de producción sin mejoras en seguridad.
  • Para personalizar la apariencia, edita los archivos de estilos ubicados en la carpeta "css".
  • Los comportamientos interactivos se encuentran en "scripts/script.js" y pueden modificarse según tus necesidades.

Contacto:
---------
Para comentar, preguntar o sugerir mejoras en el proyecto, puedes ponerte en contacto con el autor.

--------------------------------------------