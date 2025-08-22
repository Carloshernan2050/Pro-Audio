// Importa el archivo bootstrap si es necesario, como se muestra en tu código original.
import './bootstrap';

/**
 * Script para gestionar el carrusel de imágenes en el dashboard.
 * Este script maneja la navegación automática y manual del carrusel.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Variables para el estado del carrusel.
    let slideIndex = 0; // Índice de la diapositiva actual
    const slides = document.querySelectorAll('.carousel-slide'); // Todas las diapositivas
    const dots = document.querySelectorAll('.dot'); // Todos los puntos de navegación
    const prevBtn = document.querySelector('.prev-btn'); // Botón de "anterior"
    const nextBtn = document.querySelector('.next-btn'); // Botón de "siguiente"
    let autoSlideInterval; // Variable para el temporizador del carrusel automático

    /**
     * Muestra una diapositiva específica y actualiza los puntos de navegación.
     * @param {number} n El índice de la diapositiva a mostrar.
     */
    function showSlide(n) {
        // Oculta todas las diapositivas removiendo la clase 'active'
        slides.forEach(slide => slide.classList.remove('active'));
        
        // Quita la clase 'active' de todos los puntos de navegación
        dots.forEach(dot => dot.classList.remove('active'));

        // Muestra la diapositiva actual añadiendo la clase 'active'
        slides[n].classList.add('active');
        
        // Resalta el punto de navegación correspondiente a la diapositiva actual
        dots[n].classList.add('active');
    }

    /**
     * Avanza a la siguiente diapositiva.
     */
    function nextSlide() {
        slideIndex = (slideIndex + 1) % slides.length;
        showSlide(slideIndex);
    }

    /**
     * Retrocede a la diapositiva anterior.
     */
    function prevSlide() {
        slideIndex = (slideIndex - 1 + slides.length) % slides.length;
        showSlide(slideIndex);
    }

    /**
     * Inicia el carrusel automático con un temporizador.
     */
    function startAutoSlide() {
        // Asegura que no haya múltiples intervalos activos
        stopAutoSlide(); 
        // Cambia de diapositiva cada 5000 milisegundos (5 segundos)
        autoSlideInterval = setInterval(nextSlide, 5000);
    }

    /**
     * Detiene el carrusel automático.
     */
    function stopAutoSlide() {
        clearInterval(autoSlideInterval);
    }

    // --- Event Listeners ---

    // Maneja el clic en el botón de "anterior"
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            stopAutoSlide(); // Detiene el carrusel para no interferir
            prevSlide();
            startAutoSlide(); // Vuelve a iniciar el temporizador
        });
    }

    // Maneja el clic en el botón de "siguiente"
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            stopAutoSlide();
            nextSlide();
            startAutoSlide();
        });
    }

    // Maneja el clic en cada uno de los puntos de navegación
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            stopAutoSlide();
            slideIndex = index; // Actualiza el índice al punto seleccionado
            showSlide(slideIndex);
            startAutoSlide();
        });
    });

    // Inicia el carrusel automáticamente al cargar la página si hay diapositivas
    if (slides.length > 0) {
        showSlide(slideIndex);
        startAutoSlide();
    }
});
