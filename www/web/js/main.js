$(document).ready(function () {

    $("#calendar").fullCalendar({
        defaultView: 'multiColAgendaDay',
        views: {
            multiColAgendaWeek: {
                type: 'multiColAgenda',
                duration: { weeks: 1 },
                numColumns: NUM_COLUMNS,
                columnHeaders: columnHeaders,
            },
            multiColAgendaDay: {
                type: 'multiColAgenda',
                duration: { days: 3 },
                numColumns: NUM_COLUMNS,
                columnHeaders: columnHeaders,
            },
        },
        minTime : "08:00:00",
        maxTime : "19:00:00",
        slotDuration : '00:15:00',
        locale : 'ru',
        events: events,
        eventDrop: function(event) {
            //alert('Event was dragged to column ' + event.column);
        },
        scrollTime: moment(),
        allDaySlot: false,
        defaultDate : moment(),
        eventClick: function(event) {
            console.log(event);
            alert(event.title);
        }
    });
});

$("#calendar").swipe( {
    swipeStatus:function(event, phase, direction, distance, duration, fingerCount, fingerData, currentDirection)
    {
        ;
        if (phase=="start"){
            // сработает в начале swipe
        }
        if (phase=="end"){
            //сработает через 20 пикселей то число которое выбрали в threshold

            if (direction == 'left') {
                //сработает при движении влево
                console.log('swipe')
            }
            if (direction == 'right') {
                //сработает при движении вправо
                console.log('swipe')
            }
            if (direction == 'up') {
                //сработает при движении вверх
            }
            if (direction == 'down') {
                //сработает при движении вниз
            }
        }
    },
    triggerOnTouchEnd:false,
    threshold:50 // сработает через 20 пикселей
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
