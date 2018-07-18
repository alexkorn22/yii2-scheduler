$(document).ready(function () {
    
    $('#calendar').fullCalendar({ 
        defaultView : 'agendaWeek',
        allDaySlot : false,
        events : events,
        eventClick: function(event) {
            console.log(event);
            alert(event.title);
        }
    })
 
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
