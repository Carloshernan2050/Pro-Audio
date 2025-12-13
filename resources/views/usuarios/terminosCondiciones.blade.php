@extends('layouts.app')

@section('title', 'Términos y Condiciones')

@push('styles')
    @vite('resources/css/app.css')
@endpush

@section('content')
    <div class="dashboard-content-wrapper">
        <h2 class="welcome-title">Términos y Condiciones</h2>
        <p class="welcome-text">Por favor, lee cuidadosamente nuestros términos y condiciones de servicio.</p>
        
        <div class="content-card" style="max-width: 900px; margin: 20px auto; padding: 30px;">
            <h3 style="margin-bottom: 20px; color: #1f2937;">1. Aceptación de los Términos</h3>
            <p style="margin-bottom: 15px; line-height: 1.6; color: #4b5563;">
                Al utilizar los servicios de PRO AUDIO, aceptas estos términos y condiciones en su totalidad. 
                Si no estás de acuerdo con alguna parte de estos términos, no debes utilizar nuestros servicios.
            </p>

            <h3 style="margin-top: 30px; margin-bottom: 20px; color: #1f2937;">2. Servicios Ofrecidos</h3>
            <p style="margin-bottom: 15px; line-height: 1.6; color: #4b5563;">
                PRO AUDIO ofrece servicios profesionales de sonido, iluminación y eventos. Todos los servicios 
                están sujetos a disponibilidad y pueden variar según la ubicación y el tipo de evento.
            </p>

            <h3 style="margin-top: 30px; margin-bottom: 20px; color: #1f2937;">3. Cotizaciones</h3>
            <p style="margin-bottom: 15px; line-height: 1.6; color: #4b5563;">
                Las cotizaciones proporcionadas son estimaciones basadas en la información proporcionada. 
                Los precios finales pueden variar según los detalles específicos del evento y están sujetos 
                a confirmación mediante un contrato formal.
            </p>

            <h3 style="margin-top: 30px; margin-bottom: 20px; color: #1f2937;">4. Reservas y Pagos</h3>
            <p style="margin-bottom: 15px; line-height: 1.6; color: #4b5563;">
                Las reservas requieren un depósito para confirmar la fecha. El pago restante debe realizarse 
                según lo acordado en el contrato de servicio. Los términos de pago específicos se establecerán 
                en el momento de la confirmación.
            </p>

            <h3 style="margin-top: 30px; margin-bottom: 20px; color: #1f2937;">5. Cancelaciones</h3>
            <p style="margin-bottom: 15px; line-height: 1.6; color: #4b5563;">
                Las cancelaciones deben notificarse con al menos 48 horas de anticipación. Las cancelaciones 
                con menos tiempo pueden estar sujetas a cargos según lo establecido en el contrato.
            </p>

            <h3 style="margin-top: 30px; margin-bottom: 20px; color: #1f2937;">6. Responsabilidad</h3>
            <p style="margin-bottom: 15px; line-height: 1.6; color: #4b5563;">
                PRO AUDIO se compromete a proporcionar servicios de calidad. Sin embargo, no nos hacemos 
                responsables de daños indirectos o consecuentes que puedan resultar del uso de nuestros servicios.
            </p>

            <h3 style="margin-top: 30px; margin-bottom: 20px; color: #1f2937;">7. Modificaciones</h3>
            <p style="margin-bottom: 15px; line-height: 1.6; color: #4b5563;">
                Nos reservamos el derecho de modificar estos términos y condiciones en cualquier momento. 
                Los cambios entrarán en vigor inmediatamente después de su publicación.
            </p>

            <h3 style="margin-top: 30px; margin-bottom: 20px; color: #1f2937;">8. Contacto</h3>
            <p style="margin-bottom: 15px; line-height: 1.6; color: #4b5563;">
                Para cualquier pregunta sobre estos términos y condiciones, puedes contactarnos a través 
                de nuestros canales de comunicación oficiales.
            </p>

            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <p style="font-size: 0.875rem; color: #6b7280; text-align: center;">
                    Última actualización: {{ date('d/m/Y') }}
                </p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ route('inicio') }}" class="btn-primary-action" style="display: inline-block;">
                <i class="fas fa-arrow-left"></i> Volver al Inicio
            </a>
        </div>
    </div>
@endsection

