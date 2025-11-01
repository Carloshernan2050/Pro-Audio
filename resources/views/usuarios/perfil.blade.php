@extends('layouts.app')

@section('title', 'Perfil de Usuario')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/perfil.css') }}">
@endpush

@section('content')
        {{-- Contenido principal de la página de perfil (estilizado en app.css) --}}
        <main class="main-content">
            <h2 class="page-title">Mi Perfil</h2>
            <p class="page-subtitle">Aquí puedes ver y gestionar tu información personal.</p>

            <div class="profile-container">
                @if(session()->has('usuario_id') && $usuario && (
                    session('role') === 'Cliente' ||
                    session('role') === 'Administrador' ||
                    session('role') === 'Admin' ||
                    session('role') === 'Usuario' ||
                    in_array('Superadmin', (array)session('roles')) ||
                    in_array('Usuario', (array)session('roles'))
                ))
                    <div class="profile-details">
                        <p><strong>Nombres:</strong> 
                            {{ ucfirst(strtolower($usuario->primer_nombre ?? '')) }}
                            {{ $usuario->segundo_nombre ? ucfirst(strtolower($usuario->segundo_nombre)) : '' }}
                        </p>
                        <p><strong>Apellidos:</strong> 
                            {{ ucfirst(strtolower($usuario->primer_apellido ?? '')) }}
                            {{ $usuario->segundo_apellido ? ucfirst(strtolower($usuario->segundo_apellido)) : '' }}
                        </p>
                        <p><strong>Correo:</strong> {{ $usuario->correo ?? 'No registrado' }}</p>
                        <p><strong>Teléfono:</strong> {{ $usuario->telefono ?? 'No registrado' }}</p>
                    </div>
                @elseif(!session()->has('usuario_id'))
                    <p class="text-gray-300">Debes iniciar sesión para ver tu perfil.</p>
                @else
                    <p class="text-gray-300">No tienes permisos para ver esta información.</p>
                @endif

                {{-- Botón para volver al panel --}}
                    <a href="{{ route('inicio') }}" class="btn-secondary inline-block" style="margin-top: 18px;">
                        <i class="fas fa-arrow-left"></i> Volver al panel
                    </a>
                {{-- Contenedor flexible para los botones --}}
                <div class="flex items-center justify-center sm:justify-start gap-4 mt-5" style="margin-top: 26px;">
                    @if(session()->has('usuario_id'))
                        {{-- Botón para cerrar sesión --}}
                        <form action="{{ route('usuarios.cerrarSesion') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-logout">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </button>
                        </form>
                    @else
                        {{-- Invitado: botones a iniciar sesión y registrarse --}}
                        <a href="{{ route('usuarios.inicioSesion') }}" class="btn-logout"><i class="fas fa-sign-in-alt"></i> Iniciar sesión</a>
                        <a href="{{ route('usuarios.registroUsuario') }}" class="btn-logout"><i class="fas fa-user-plus"></i> Registrarse</a>
                    @endif
                </div>
            </div>
        </main>
@endsection
