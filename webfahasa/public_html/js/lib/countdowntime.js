/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

const CountDownTime = function () {
    
    var $this = this;
    var countdown_timer_block;
    var $backgroundImageCountDown;
    
    this.startCountDownTimeBlock = function (future_date_time,end_date_time,future_image,end_image,flag) {
//        console.log("future_date_time",future_date_time)
//        console.log("end_date_time",end_date_time)
//        console.log("future_image",future_image)
//        console.log("end_image",end_image)
//        console.log("flag",flag)
        
        let dayCountDown = $jq(".item-countdowntime-day")
        let hoursCountDown = $jq(".item-countdowntime-hour");
        let minutesCountDown = $jq(".item-countdowntime-min");
        let secondsCountDown = $jq(".item-countdowntime-sec");
        $backgroundImageCountDown = $jq('.image-countdowntime');
        let date_time;
        let firstRender = false;
        
        if(countdown_timer_block){
            clearInterval(countdown_timer_block);
        }
        if(flag == 'start'){
            date_time = future_date_time;
        }
        if(flag == 'run'){
           date_time = end_date_time;
        }
        $this.calculateTime2(date_time, dayCountDown, hoursCountDown, minutesCountDown, secondsCountDown);
        countdown_timer_block = setInterval(function () {
            var dt = new Date();
            var d1 = new Date(future_date_time);
//            let formatDate = (dt.getMonth() + 1).toString().padStart(2, '0') + '/' + dt.getDate().toString().padStart(2, '0') + '/' + dt.getFullYear().toString().padStart(4, '0')
//                    + ' ' + dt.getHours().toString().padStart(2, '0') + ':' + dt.getMinutes().toString().padStart(2, '0') + ':' + dt.getSeconds().toString().padStart(2, '0');
            if (dt >= d1 && !firstRender) {
                date_time = end_date_time;
                firstRender = true;
                $backgroundImageCountDown.css('background-image', 'url(' + end_image + ')');
            } 
                $this.calculateTime2(date_time, dayCountDown, hoursCountDown, minutesCountDown, secondsCountDown);
        }, 1000);
    }
    
    this.calculateTime2 = function(future_date_time, $day, $hours, $minutes, $seconds){
        let vn_time_moment = moment(new Date()).utcOffset(7);
        let vn_time_formated = vn_time_moment.format("YYYY/MM/DD HH:mm:ss");
        let time = Helper.substractDates(vn_time_formated, future_date_time);
        
        if (time.days == 0 && time.hours == 0 && time.minutes == 0 && time.seconds == 0) {
            clearInterval(countdown_timer_block);
        }
        $day.text(Helper.zeroPad(time.days, 2));
        $hours.text(Helper.zeroPad(time.hours, 2));
        $minutes.text(Helper.zeroPad(time.minutes, 2));
        $seconds.text(Helper.zeroPad(time.seconds, 2));
    }
    
}
