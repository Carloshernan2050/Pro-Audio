
flowchart TD
    Start([Inicio]) --> Auth{Usuario<br/>Autenticado como<br/>Administrador?}
    Auth -->|No| End1([Acceso Denegado])
    Auth -->|Sí| AccessCalendar[Acceder al módulo<br/>de Calendario]
    AccessCalendar --> LoadView[Cargar Vista<br/>Blade de Calendario]
    LoadView --> QueryDB[Consultar Eventos<br/>Activos en Base de Datos]
    QueryDB --> LoadRelations[Cargar Relaciones:<br/>- Items asociados<br/>- Reservas vinculadas<br/>- Movimientos de inventario]
    LoadRelations --> ProcessData[Procesar Datos:<br/>- Eliminar duplicados<br/>- Ordenar por fecha descendente<br/>- Transformar para visualización]
    ProcessData --> CheckEmpty{¿Hay<br/>Eventos?}
    CheckEmpty -->|No| ShowEmpty[Mostrar Mensaje:<br/>No hay eventos programados]
    CheckEmpty -->|Sí| DisplayTable[Mostrar Tabla con<br/>Eventos Activos:<br/>- Título del evento<br/>- Fechas inicio y fin<br/>- Descripción<br/>- Equipos/Items asociados<br/>- Reserva asociada si existe]
    DisplayTable --> End([Fin - Vista del Calendario])
    ShowEmpty --> End

   


