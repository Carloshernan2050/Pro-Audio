<header class="top-bar">
    <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Menú">
        <i class="fas fa-bars"></i>
    </button>
    <a href="{{ route('inicio') }}" class="brand-logo" title="PROAUDIO">
        <img src="{{ Vite::asset('resources/images/logo-proaudio.svg') }}" alt="PROAUDIO" />
    </a>
    <form class="search-form" action="{{ route('buscar') }}" method="GET">
        <input type="text" name="buscar" class="search-input" placeholder="Buscar servicios..." value="{{ request('buscar') ?? '' }}">
        <button type="submit" class="search-btn">
            <i class="fas fa-search"></i>
        </button>
    </form>
    @php
        $usuarioId = session('usuario_id');
        $roles = session('roles', []);
        $roles = is_array($roles) ? $roles : [$roles];
        $esInvitado = in_array('Invitado', $roles, true) || !$usuarioId;
        $usuario = $usuarioId ? \App\Models\Usuario::find($usuarioId) : null;
    @endphp
    
    @if($esInvitado)
        <a href="{{ route('usuarios.inicioSesion') }}" class="btn-iniciar-sesion" title="Iniciar sesión">
            <i class="fas fa-sign-in-alt"></i>
            <span>Iniciar sesión</span>
        </a>
    @else
        <button onclick="openProfileModal()" class="profile-btn-header" title="Perfil" style="background:none; border:none; cursor:pointer; padding:0;">
            @php
                $fotoPerfil = null;
                if ($usuario && $usuario->foto_perfil) {
                    // Si el usuario tiene foto_perfil en la BD, intentar mostrarla
                    // Verificar múltiples ubicaciones posibles del archivo
                    $path1 = storage_path('app/public/perfiles/' . $usuario->foto_perfil);
                    $path2 = public_path('storage/perfiles/' . $usuario->foto_perfil);
                    
                    // Intentar mostrar la imagen siempre que exista en BD
                    // El navegador mostrará un ícono roto si el archivo realmente no existe
                        $fotoPerfil = asset('storage/perfiles/' . $usuario->foto_perfil);
                }
                $iniciales = $usuario ? strtoupper(substr($usuario->primer_nombre ?? 'U', 0, 1) . substr($usuario->primer_apellido ?? 'S', 0, 1)) : null;
            @endphp
            @if($fotoPerfil)
                <img id="profile-img-{{ $usuarioId ?? 'default' }}" src="{{ $fotoPerfil }}" alt="Perfil" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #e91c1c;">
                <div id="profile-fallback-{{ $usuarioId ?? 'default' }}" style="width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg, #e91c1c 0%, #c81a1a 100%); display:none; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:16px; border:2px solid #e91c1c;">
                    {{ $iniciales ?? 'U' }}
                </div>
            @elseif($usuario && $iniciales)
                <div style="width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg, #e91c1c 0%, #c81a1a 100%); display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:16px; border:2px solid #e91c1c;">
                    {{ $iniciales }}
                </div>
            @else
                <div style="width:40px; height:40px; border-radius:50%; background:#000000; display:flex; align-items:center; justify-content:center; color:white; font-size:18px; border:2px solid #e91c1c;">
                    <i class="fas fa-user"></i>
                </div>
            @endif
        </button>
    @endif
</header>

@if($fotoPerfil && !$esInvitado)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileImg = document.getElementById('profile-img-{{ $usuarioId ?? 'default' }}');
        const profileFallback = document.getElementById('profile-fallback-{{ $usuarioId ?? 'default' }}');
        
        if (profileImg && profileFallback) {
            profileImg.addEventListener('error', function() {
                this.style.display = 'none';
                if (profileFallback) {
                    profileFallback.style.display = 'flex';
                }
            });
            
            profileImg.addEventListener('load', function() {
                if (profileFallback) {
                    profileFallback.style.display = 'none';
                }
            });
        }
    });
</script>
@endif
