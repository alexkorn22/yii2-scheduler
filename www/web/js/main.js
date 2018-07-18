$(document).ready(function () {

    $("#calendar").fullCalendar({
        defaultView: 'multiColAgendaWeek',
        views: {
            multiColAgendaDay: {
                type: 'multiColAgenda',
                duration: { days: 1 },
                numColumns: NUM_COLUMNS,
                columnHeaders: ['Column 1', 'Column 2']
            },
            multiColAgendaWeek: {
                type: 'multiColAgenda',
                duration: { weeks: 1 },
                numColumns: NUM_COLUMNS,
                columnHeaders: ['Col. 1', 'Col. 2']
            }
        },
        events: events,
        eventDrop: function(event) {
            //alert('Event was dragged to column ' + event.column);
        },
        scrollTime: moment(),
        allDaySlot: false,
        defaultDate : moment(),
    });
});

function getEvents() {
    let events = [];
    let startDay = moment().startOf('day').add(-1,'h');

    let curTime = startDay.add(8,'h');
    let curHour = 8;
    while (curHour < 17 ) {
        let start =  moment().startOf('day').add(curHour,'h');
        let end = moment().startOf('day').add(curHour,'h').add(1, "h"); 
        events.push(
            {
                title: faker.commerce.productName(),
                start: start,
                end: end,
                column: 0,
                editable: true,
                id: curHour,
            }
        );
        curHour++;
    }

    
    return events;
}
