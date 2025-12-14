{{-- Componente Sidebar reutilizable --}}
@php
    use App\Models\Servicios;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Schema;
    
    // Servicios predefinidos con rutas específicas
    $serviciosPredefinidos = [];
    try {
        $serviciosPredefinidos = [
            'Animación' => route('usuarios.animacion'),
            'Publicidad' => route('usuarios.publicidad'),
            'Alquiler' => route('usuarios.alquiler'),
        ];
    } catch (\Exception $e) {
        // Si las rutas no existen, usar array vacío
        $serviciosPredefinidos = [];
    }
    
    // Cargar todos los servicios de la base de datos con manejo de errores
    $todosLosServicios = collect([]);
    try {
        // Verificar que la tabla exista antes de consultar
        if (Schema::hasTable('servicios')) {
            $todosLosServicios = Servicios::orderBy('nombre_servicio')->get();
        }
    } catch (\Exception $e) {
        // Si hay error, usar colección vacía
        $todosLosServicios = collect([]);
    }
    
    // Separar servicios predefinidos y servicios creados por el usuario
    $serviciosUsuario = $todosLosServicios->filter(function($servicio) use ($serviciosPredefinidos) {
        if (empty($servicio->nombre_servicio)) {
            return false; // Excluir servicios sin nombre
        }
        // Comparación case-insensitive para evitar duplicados
        $nombreNormalizado = strtolower(trim($servicio->nombre_servicio));
        foreach ($serviciosPredefinidos as $nombrePredefinido => $ruta) {
            if (strtolower(trim($nombrePredefinido)) === $nombreNormalizado) {
                return false; // Es un servicio predefinido
            }
        }
        return true; // Es un servicio creado por el usuario
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
            @php
                try {
                    $rutaInicio = route('inicio');
                } catch (\Exception $e) {
                    $rutaInicio = '#';
                }
            @endphp
            <a href="{{ $rutaInicio }}" class="sidebar-btn"><i class="fas fa-home"></i> Inicio</a>
            
            {{-- Servicios predefinidos --}}
            @foreach($serviciosPredefinidos as $nombre => $ruta)
                @php
                    // Buscar servicio en la base de datos (comparación case-insensitive)
                    $servicioExistente = $todosLosServicios->first(function($servicio) use ($nombre) {
                        return strtolower(trim($servicio->nombre_servicio)) === strtolower(trim($nombre));
                    });
                @endphp
                @if($servicioExistente)
                    @php
                        // Obtener icono: primero del servicio, luego del array de iconos, luego default
                        $icono = !empty($servicioExistente->icono)
                            ? $servicioExistente->icono
                            : (!empty($iconos[$nombre]) ? $iconos[$nombre] : $iconoDefault);
                    @endphp
                    <a href="{{ $ruta }}" class="sidebar-btn">
                        <i class="{{ $icono }}"></i> {{ $nombre }}
                    </a>
                @endif
            @endforeach
            
            {{-- Servicios creados por el usuario --}}
            @foreach($serviciosUsuario as $servicio)
                @php
                    try {
                        if (empty($servicio->nombre_servicio)) {
                            continue; // Saltar si no tiene nombre
                        }
                        $slug = Str::slug($servicio->nombre_servicio, '_');
                        $ruta = route('usuarios.servicio', ['slug' => $slug]);
                        $icono = !empty($servicio->icono)
                            ? $servicio->icono
                            : (!empty($iconos[$servicio->nombre_servicio]) ? $iconos[$servicio->nombre_servicio] : $iconoDefault);
                    } catch (\Exception $e) {
                        // Si hay error generando la ruta, saltar este servicio
                        continue;
                    }
                @endphp
                <a href="{{ $ruta }}" class="sidebar-btn">
                    <i class="{{ $icono }}"></i> {{ $servicio->nombre_servicio }}
                </a>
            @endforeach
            
            @if($noInvitado)
                @php
                    try {
                        $rutaCalendario = route('usuarios.calendario');
                    } catch (\Exception $e) {
                        $rutaCalendario = '#';
                    }
                @endphp
                <a href="{{ $rutaCalendario }}" class="sidebar-btn"><i class="fas fa-calendar-alt"></i> Calendario</a>
                
                @php
                    try {
                        $rutaHistorial = route('cotizaciones.historial');
                    } catch (\Exception $e) {
                        $rutaHistorial = '#';
                    }
                @endphp
                <a href="{{ $rutaHistorial }}" class="sidebar-btn"><i class="fas fa-history"></i> Historial</a>
            @endif

            {{-- Ajustes: Admin o Superadmin --}}
            @if($esSuperadmin || in_array('Admin', $rolesSesion, true) || in_array('Administrador', $rolesSesion, true))
                @php
                    try {
                        $rutaAjustes = route('usuarios.ajustes');
                    } catch (\Exception $e) {
                        $rutaAjustes = '#';
                    }
                @endphp
                <a href="{{ $rutaAjustes }}" class="sidebar-btn"><i class="fas fa-cog"></i> Ajustes</a>
                @if($esSuperadmin)
                    @php
                        try {
                            $rutaRoles = route('admin.roles.index');
                        } catch (\Exception $e) {
                            $rutaRoles = '#';
                        }
                    @endphp
                    <a href="{{ $rutaRoles }}" class="sidebar-btn"><i class="fas fa-user-shield"></i> Control de roles</a>
                @endif
            @endif

            {{-- Chatbot movido a botón flotante global; se elimina del menú lateral --}}
        </div>
    </div>
</aside>

