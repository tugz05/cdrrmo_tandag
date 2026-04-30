import TimeAgo from 'javascript-time-ago';
import en from 'javascript-time-ago/locale/en.json';

TimeAgo.addDefaultLocale(en)

export const timeAgo = (time) => {   
    if (time == '') return;
    const timeAgo = new TimeAgo('en-US')
    return timeAgo.format(new Date(time) - 60 * 1000); 
}
