// Función para hacer arrastrable un elemento
function hacerArrastrable(el) {
    let offsetX = 0, offsetY = 0, arrastrando = false;
    el.addEventListener("mousedown", function(e) {
        arrastrando = true;
        offsetX = e.clientX - el.getBoundingClientRect().left;
        offsetY = e.clientY - el.getBoundingClientRect().top;
        el.style.cursor = "grabbing";
        el.style.zIndex = 9999;
    });
    document.addEventListener("mousemove", function(e) {
        if (!arrastrando) return;
        el.style.left = (e.clientX - offsetX) + "px";
        el.style.top = (e.clientY - offsetY) + "px";
    });
    document.addEventListener("mouseup", function() {
        arrastrando = false;
        el.style.cursor = "grab";
        el.style.zIndex = 1;
    });
}

// Agregar eventos extra para eliminar y duplicar
function attachExtraEvents(articulo) {
    const btnEliminar = articulo.querySelector(".btnEliminar");
    if (btnEliminar) {
        btnEliminar.addEventListener("click", function(e) {
            e.stopPropagation();
            articulo.remove();
        });
    }
    const btnDuplicar = articulo.querySelector(".btnDuplicar");
    if (btnDuplicar) {
        btnDuplicar.addEventListener("click", function(e) {
            e.stopPropagation();
            const clon = articulo.cloneNode(true);
            clon.style.left = (parseInt(articulo.style.left, 10) + 20) + "px";
            clon.style.top = (parseInt(articulo.style.top, 10) + 20) + "px";
            document.querySelector("main").appendChild(clon);
            hacerArrastrable(clon);
            attachExtraEvents(clon);
        });
    }
}

// Recolectar la información de las clases del DOM
function obtenerClases() {
    const lista = [];
    document.querySelectorAll("article.draggable").forEach(function(articulo) {
        const nombre = articulo.querySelector(".nombre")?.textContent.trim() || "Clase";
        const propiedades = [];
        articulo.querySelectorAll(".propiedades ul li").forEach(function(li) {
            propiedades.push(li.textContent.trim());
        });
        const metodos = [];
        articulo.querySelectorAll(".metodos ul li").forEach(function(li) {
            metodos.push(li.textContent.trim());
        });
        const posX = parseInt(articulo.style.left, 10) || 250;
        const posY = parseInt(articulo.style.top, 10) || 250;
        lista.push({
            className: nombre,
            properties: propiedades,
            methods: metodos,
            x: posX,
            y: posY
        });
    });
    return lista;
}

// Enviar los datos de las clases al servidor para guardar
function guardarClases() {
    const datos = obtenerClases();
    fetch('index.php?ajax=guardar_clases', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(response => response.text())
    .then(texto => {
        alert(texto);
        console.log(texto);
    })
    .catch(error => console.error("Error al guardar clases:", error));
}

// Cargar las clases desde el servidor
function cargarClases() {
    fetch('index.php?ajax=cargar_clases')
    .then(response => response.json())
    .then(data => {
        if (Array.isArray(data)) {
            document.querySelectorAll("article.draggable").forEach(el => el.remove());
            data.forEach(function(clase) {
                const plantilla = document.getElementById("plantilla-clase");
                const clon = plantilla.content.cloneNode(true);
                const articulo = clon.querySelector("article");
                articulo.querySelector(".nombre").textContent = clase.className;
                const ulProps = articulo.querySelector(".propiedades ul");
                ulProps.innerHTML = "";
                (clase.properties || []).forEach(function(prop) {
                    const li = document.createElement("li");
                    li.textContent = prop;
                    ulProps.appendChild(li);
                });
                const ulMets = articulo.querySelector(".metodos ul");
                ulMets.innerHTML = "";
                (clase.methods || []).forEach(function(met) {
                    const li = document.createElement("li");
                    li.textContent = met;
                    ulMets.appendChild(li);
                });
                articulo.style.left = (clase.x || 250) + "px";
                articulo.style.top  = (clase.y || 250) + "px";
                document.querySelector("main").appendChild(articulo);
                hacerArrastrable(articulo);
                attachExtraEvents(articulo);
            });
        } else if (data.error) {
            console.warn(data.error);
        }
    })
    .catch(error => console.error("Error cargando clases:", error));
}

// Función para alternar el estado del menú lateral (nav)
function toggleNav() {
    const nav = document.getElementById("sideNav");
    nav.classList.toggle("collapsed");
}

// Configurar eventos una vez cargado el DOM
document.addEventListener("DOMContentLoaded", function() {
    // Evento del botón para plegar/desplegar el nav
    document.getElementById("toggleNav").addEventListener("click", function(e) {
        toggleNav();
    });
    
    document.getElementById("agregarClase").addEventListener("click", function(e) {
        e.preventDefault();
        const plantilla = document.getElementById("plantilla-clase");
        const clon = plantilla.content.cloneNode(true);
        const articulo = clon.querySelector("article");
        document.querySelector("main").appendChild(articulo);
        hacerArrastrable(articulo);
        attachExtraEvents(articulo);
    });
    
    document.getElementById("mostrarClases").addEventListener("click", function(e) {
        e.preventDefault();
        console.log(obtenerClases());
    });
    
    document.getElementById("guardarClases").addEventListener("click", function(e) {
        e.preventDefault();
        guardarClases();
    });
    
    // Auto-guardado cada 30 segundos
    setInterval(guardarClases, 30000);
    
    // Cargar clases al inicio
    cargarClases();
});