{{-- Componente Sidebar reutilizable --}}
@php
    use App\Models\Servicios;
    use Illuminate\Support\Str;
    
    // Servicios predefinidos con rutas específicas
    $serviciosPredefinidos = [
        'Animación' => route('usuarios.animacion'),
        'Publicidad' => route('usuarios.publicidad'),
        'Alquiler' => route('usuarios.alquiler'),
    ];
    
    // Cargar todos los servicios de la base de datos
    $todosLosServicios = Servicios::orderBy('nombre_servicio')->get();
    
    // Separar servicios predefinidos y servicios creados por el usuario
    $serviciosUsuario = $todosLosServicios->filter(function($servicio) use ($serviciosPredefinidos) {
        return !isset($serviciosPredefinidos[$servicio->nombre_servicio]);
    });
    
    // Iconos por servicio definidos manualmente (opcional)
    $iconos = [];
    
    // Icono por defecto para servicios nuevos
    $iconoDefault = 'fas fa-tag';

    $rolesSesion = session('roles') ?? [session('role')];
    $rolesSesion = is_array($rolesSesion) ? $rolesSesion : [$rolesSesion];
    $esSuperadmin = in_array('Superadmin', $rolesSesion, true);
    $noInvitado = !in_array('Invitado', $rolesSesion, true);
@endphp

<aside class="sidebar">
    <div class="sidebar-scroll-wrapper">
        <div class="sidebar-scroll-inner">
            <h5 class="menu-title">Menú</h5>
            <a href="{{ route('inicio') }}" class="sidebar-btn"><i class="fas fa-home"></i> Inicio</a>
            
            {{-- Servicios predefinidos --}}
            @foreach($serviciosPredefinidos as $nombre => $ruta)
                @php
                    $servicioExistente = $todosLosServicios->firstWhere('nombre_servicio', $nombre);
                    $icono = $servicioExistente && $servicioExistente->icono
                        ? $servicioExistente->icono
                        : ($iconos[$nombre] ?? $iconoDefault);
                @endphp
                <a href="{{ $ruta }}" class="sidebar-btn">
                    <i class="{{ $icono }}"></i> {{ $nombre }}
                </a>
            @endforeach
            
            {{-- Servicios creados por el usuario --}}
            @foreach($serviciosUsuario as $servicio)
                @php
                    $slug = Str::slug($servicio->nombre_servicio, '_');
                    $ruta = route('usuarios.servicio', ['slug' => $slug]);
                    $icono = $servicio->icono ?: ($iconos[$servicio->nombre_servicio] ?? $iconoDefault);
                @endphp
                <a href="{{ $ruta }}" class="sidebar-btn">
                    <i class="{{ $icono }}"></i> {{ $servicio->nombre_servicio }}
                </a>
            @endforeach
            
            @if($noInvitado)
            <a href="{{ route('usuarios.calendario') }}" class="sidebar-btn"><i class="fas fa-calendar-alt"></i> Calendario</a>
            @endif

            {{-- Ajustes: Admin o Superadmin --}}
            @if($esSuperadmin || in_array('Admin', $rolesSesion, true) || in_array('Administrador', $rolesSesion, true))
            <a href="{{ route('usuarios.ajustes') }}" class="sidebar-btn"><i class="fas fa-cog"></i> Ajustes</a>
                @if($esSuperadmin)
                <a href="{{ route('admin.roles.index') }}" class="sidebar-btn"><i class="fas fa-user-shield"></i> Control de roles</a>
                @endif
            @endif

            {{-- Chatbot movido a botón flotante global; se elimina del menú lateral --}}
        </div>
    </div>
</aside>

