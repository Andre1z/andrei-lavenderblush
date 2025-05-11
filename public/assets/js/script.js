// public/assets/js/script.js

/**
 * Permite hacer "draggable" cualquier elemento.
 * Se agrega la funcionalidad para mover el elemento arrastrándolo.
 * @param {HTMLElement} el - Elemento a hacerlo draggable.
 */
function makeDraggable(el) {
    let offsetX = 0,
        offsetY = 0;
    let isDragging = false;

    el.addEventListener("mousedown", function(e) {
        isDragging = true;
        offsetX = e.clientX - el.getBoundingClientRect().left;
        offsetY = e.clientY - el.getBoundingClientRect().top;
        el.style.cursor = "grabbing";
        el.style.zIndex = "1000";
    });

    document.addEventListener("mousemove", function(e) {
        if (!isDragging) return;
        el.style.left = (e.clientX - offsetX) + "px";
        el.style.top = (e.clientY - offsetY) + "px";
    });

    document.addEventListener("mouseup", function() {
        isDragging = false;
        el.style.cursor = "grab";
        el.style.zIndex = "1";
    });
}

/**
 * Recorre el área de trabajo y obtiene un arreglo con los datos
 * de cada elemento draggable (clase), incluyendo nombre, propiedades, métodos y posición.
 * @returns {Array} Arreglo de objetos con la información de cada clase.
 */
function getClasses() {
    const articles = document.querySelectorAll("article.draggable");
    const classesArray = [];

    articles.forEach(function(article) {
        const className = article.querySelector(".nombre").textContent.trim() || "Clase";

        // Recopila las propiedades de la clase
        const properties = [];
        article.querySelectorAll(".propiedades ul li").forEach(function(li) {
            const text = li.textContent.trim();
            if (text) properties.push(text);
        });

        // Recopila los métodos de la clase
        const methods = [];
        article.querySelectorAll(".metodos ul li").forEach(function(li) {
            const text = li.textContent.trim();
            if (text) methods.push(text);
        });

        // Obtiene la posición (se asume que viene definida en estilo inline)
        const x = parseInt(article.style.left, 10) || 250;
        const y = parseInt(article.style.top, 10) || 250;

        classesArray.push({
            className: className,
            properties: properties,
            methods: methods,
            x: x,
            y: y
        });
    });

    return classesArray;
}

/**
 * Imprime en la consola el arreglo obtenido de getClasses().
 */
function listClasses() {
    console.log(getClasses());
}

/**
 * Envía mediante AJAX (fetch) la información de las clases al backend.
 * Se utiliza como endpoint "index.php?ajax=save_classes", el cual debe capturar y guardar estos datos.
 */
function saveClasses() {
    const data = getClasses();

    fetch('index.php?ajax=save_classes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.text())
    .then(result => {
        alert(result);
        console.log(result);
    })
    .catch(error => {
        console.error("Error saving classes:", error);
    });
}

/**
 * Recupera mediante AJAX los datos de las clases del proyecto actual y los renderiza
 * en el área de trabajo (main). Se utiliza el endpoint "index.php?ajax=load_classes".
 */
function loadClasses() {
    fetch('index.php?ajax=load_classes')
    .then(response => response.json())
    .then(data => {
        if (Array.isArray(data)) {
            // Elimina las clases existentes
            document.querySelectorAll("article.draggable").forEach(el => el.remove());

            // Recorre el arreglo recibido desde el backend
            data.forEach(function(cls) {
                const template = document.getElementById("article-template");
                const clone = template.content.cloneNode(true);
                const article = clone.querySelector("article");

                // Asigna el nombre de la clase
                article.querySelector(".nombre").textContent = cls.className;

                // Rellena la lista de propiedades
                const propsUl = article.querySelector(".propiedades ul");
                propsUl.innerHTML = "";
                (cls.properties || []).forEach(function(prop) {
                    const li = document.createElement("li");
                    li.textContent = prop;
                    propsUl.appendChild(li);
                });

                // Rellena la lista de métodos
                const methodsUl = article.querySelector(".metodos ul");
                methodsUl.innerHTML = "";
                (cls.methods || []).forEach(function(met) {
                    const li = document.createElement("li");
                    li.textContent = met;
                    methodsUl.appendChild(li);
                });

                // Posición del elemento
                article.style.left = (cls.x || 250) + "px";
                article.style.top = (cls.y || 250) + "px";

                document.querySelector("main").appendChild(article);
                makeDraggable(article);
            });
        } else if(data.error) {
            console.error(data.error);
        }
    })
    .catch(error => console.error("Error loading classes:", error));
}

// Configura los event listeners una vez el DOM está completamente cargado
document.addEventListener("DOMContentLoaded", function() {
    const addBtn = document.getElementById("addBtn");
    const listBtn = document.getElementById("listBtn");
    const saveBtn = document.getElementById("saveBtn");

    if (addBtn) {
        addBtn.addEventListener("click", function(e) {
            e.preventDefault();
            const template = document.getElementById("article-template");
            const clone = template.content.cloneNode(true);
            const article = clone.querySelector("article");

            // Ubicación inicial predeterminada
            article.style.left = "250px";
            article.style.top = "250px";

            document.querySelector("main").appendChild(article);
            makeDraggable(article);
        });
    }

    if (listBtn) {
        listBtn.addEventListener("click", function(e) {
            e.preventDefault();
            listClasses();
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener("click", function(e) {
            e.preventDefault();
            saveClasses();
        });
    }

    // Al cargar la página, se intentan cargar las clases existentes para el proyecto.
    loadClasses();
});