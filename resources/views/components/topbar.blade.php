<header class="top-bar">
    <a href="{{ route('inicio') }}" class="brand-logo" title="PROAUDIO">
        <img src="{{ Vite::asset('resources/images/logo-proaudio.svg') }}" alt="PROAUDIO" />
    </a>
    <form class="search-form" action="{{ route('buscar') }}" method="GET">
        <input type="text" name="buscar" class="search-input" placeholder="Buscar servicios..." value="{{ request('buscar') ?? '' }}">
        <button type="submit" class="search-btn">
            <i class="fas fa-search"></i>
        </button>
    </form>
    <a href="{{ route('usuarios.perfil') }}" class="profile-btn-header" title="Perfil">
        <i class="fas fa-user" style="font-size:2rem;"></i>
    </a>
</header>
