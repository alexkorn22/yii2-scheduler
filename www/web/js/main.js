$(function() { // document ready
    $('#calendar').fullCalendar({
        schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
        defaultView: 'agendaDay',
       // defaultDate: '2018-04-07',
        //editable: true,
        nowIndicator: true,
        //selectable: true,
        eventLimit: true, // allow "more" link when too many events
        // time
        slotLabelFormat : "HH:mm",
        minTime: "08:00:00",
        maxTime: "19:00:00",
        slotDuration : "00:15:00",
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'agendaDay,agendaWeek'
        },
        views: {
            agendaTwoDay: {
                type: 'agenda',
                duration: { days: 2 },

                // views that are more than a day will NOT do this behavior by default
                // so, we need to explicitly enable it
                groupByResource: true,

                //// uncomment this line to group by day FIRST with resources underneath
                groupByDateAndResource: true
            },
        },
        //// uncomment this line to hide the all-day slot
        allDaySlot: false,
        resources:resources,
        events: events,
        eventClick: eventClick
    });


});

var runEventClick = false;

eventClick = function(event) {
    if (runEventClick) {
        return;
    }
    runEventClick = true;
    $.ajax({
        type: 'POST',
        url: urlEditEvent,
        data: {
            action: 'open',
            eventId : event.id,
            idMedWorker : event.idMedWorker,
            start: event.start.format(),
            end: event.end.format(),
            clientId: event.clientId,
            title: event.title,
            description: event.description,
        },
        success : function (data) {
            $("#modalContainer").html(data);
            $("#modalEvent").modal('show');
            $('.datepicker').datepicker({
                dateFormat: "yy-mm-dd"
            });
            runEventClick = false;
        },
        error : function (data) {
            console.error(data);
            runEventClick = false;
        }
        });

};