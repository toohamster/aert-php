<?php namespace Aert;
/**
 * 时间格式化辅助类
 * 
 * @author 449211678@qq.com
 */
class DateFormat
{
    private static $_DIFF_FORMAT = array(
        'DAY'           => '%s天',
        'DAY_HOUR'      => '%s天%s小时',
        'HOUR'          => '%s小时',
        'HOUR_MINUTE'   => '%s小时%s分',
        'MINUTE'        => '%s分钟',
        'MINUTE_SECOND' => '%s分钟%s秒',
        'SECOND'        => '%s秒',
    );
 
    /**
     * 时间段友好格式化
     * 
     * @param int $seconds 秒
     * @param bool $nosecond 是否需要精确到秒,如果不需要则只精确到分钟
     * @param  int  $ceilorfloor 不精确到秒时的取舍处理 ceil 进1 , floor 舍去
     * 
     * @return string
     */
    public static function timeslot($seconds, $nosecond=false, $ceilorfloor='ceil') 
    {
        $formats = self::$_DIFF_FORMAT;
        
        if ( $seconds <= 0 ) return 0;

        $minutes = floor($seconds / 60);
        $hours   = floor($minutes / 60);
        $days    = floor($hours / 24);
 
        if ($days > 0)
        {
            $diffFormat = 'DAY';
            if ( ($hours - $days * 24) > 0 )
            {
                $diffFormat = 'DAY_HOUR';
            }
        }
        else
        {
            if ( $hours > 0 )
            {
                $diffFormat = 'HOUR';
                if ( ($minutes - $hours * 60) > 0 )
                {
                    $diffFormat = 'HOUR_MINUTE';
                }
            } 
            else if ( $minutes > 0 )
            {
                $diffFormat = 'MINUTE';
                if ( ($seconds - $minutes * 60) > 0 )
                {
                    if ( $nosecond )
                    {
                        if ( $ceilorfloor == 'ceil' )
                        {
                            $minutes += 1;
                        }
                    }
                    else
                    {
                        $diffFormat = 'MINUTE_SECOND';
                    }                    
                }
            }
            else
            {
                if ( $nosecond )
                {
                    $diffFormat = 'MINUTE';
                    $minutes = 1;
                    
                    if ( $ceilorfloor == 'ceil' )
                    {
                        // 
                    }
                    
                }
                else
                {
                    $diffFormat = 'SECOND';
                }
                
            }
        }

        $dateDiff = null;
        switch ($diffFormat) {
            case 'DAY':
                $dateDiff = sprintf($formats[$diffFormat], $days);
                break;
            case 'DAY_HOUR':
                $dateDiff = sprintf($formats[$diffFormat], $days, $hours - $days * 24);
                break;
            case 'HOUR':
                $dateDiff = sprintf($formats[$diffFormat], $hours);
                break;
            case 'HOUR_MINUTE':
                $dateDiff = sprintf($formats[$diffFormat], $hours, $minutes - $hours * 60);
                break;
            case 'MINUTE':
                $dateDiff = sprintf($formats[$diffFormat], $minutes);
                break;
            case 'MINUTE_SECOND':
                $dateDiff = sprintf($formats[$diffFormat], $minutes, $seconds - $minutes * 60);
                break;
            case 'SECOND':
                $dateDiff = sprintf($formats[$diffFormat], $seconds);
                break;
        }
        return $dateDiff;
    }
}