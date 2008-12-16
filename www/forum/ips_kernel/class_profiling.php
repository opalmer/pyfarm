<?php
/*
+--------------------------------------------------------------------------
|   Invision Power Services Kernel [Application Profiling]
|	Invision Power Board
|   =============================================
|   by Matthew Mecham
|   (c) 2001 - 2006 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionboard.com
+---------------------------------------------------------------------------
|   THIS IS NOT FREE / OPEN SOURCE SOFTWARE
+---------------------------------------------------------------------------
|
|   > Application Profiling
|   > Module written by Brandon Farber
|   > Date started: Monday 28th February 2005 16:46 
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/

/**
* IPS Kernel Pages: Application Profiling Layer [PHP5]
*
* @package		IPS_KERNEL
* @subpackage	ApplicationProfiling
* @author		Brandon Farber
* @copyright	Invision Power Services, Inc.
* @version		1.0
*/

/**
 * Gateway for application profiling
 *
 *	$a = new class_profiling();
 *	$a->output_results( true, false );
 */
 
//-----------------------------------------
// Define KERNEL_PATH if not
// defined - i.e. installation
//-----------------------------------------
 
if ( ! defined('KERNEL_PATH') )
{
 	if( defined('INS_KERNEL_PATH') )
 	{
	 	define( 'KERNEL_PATH', INS_KERNEL_PATH );
 	}
 	else
 	{
	 	define( 'KERNEL_PATH', str_replace( "//", "/", str_replace( "\\", "/", dirname( __FILE__ ) ) ) . "/" );
 	}
}

//-----------------------------------------
// Define ROOT_PATH if not
//-----------------------------------------
 
if ( ! defined('ROOT_PATH') )
{
	$path 		= str_replace( "//", "/", str_replace( "\\", "/", dirname( __FILE__ ) ) );
	$path_bits	= explode( "/", $path );
	array_pop( $path_bits );
	
 	define( 'ROOT_PATH', implode( "/", $path_bits ) . "/" );
}


class class_profiling
{
	var $start_time			= array();
	var $end_time			= array();
	var $running_time		= array();
	
	var $start_memory		= array();
	var $end_memory			= array();
	
	var $descriptions		= array();
	
	var $timer_stack		= array();
	var $internal_count		= array();
	var $stack_trace		= null;
	
	var $cur_timer			= null;
	var $init_time			= 0;
	
	var $init_memory		= 0;
	var $total_memory		= 0;
	var $peak_memory		= 0;
	
	
	function __construct( $start_apd = false )
	{
		$this->start_time		= array();
		$this->end_time			= array();
		$this->running_time		= array();
		
		$this->start_memory		= array();
		$this->end_memory		= array();
		
		$this->descriptions		= array();
		
		$this->timer_stack		= array();
		$this->internal_count	= array();
		$this->stack_trace		= "Profiling Class Initialized\n";
		
		$this->cur_timer		= null;
		$this->init_time		= $this->get_micro_seconds();
		
		$this->init_memory		= $this->get_current_memory();
		$this->total_memory		= $this->init_memory;
		$this->peak_memory		= $this->init_memory;
		
		if( $start_apd == true && function_exists( 'apd_set_pprof_trace' ) )
		{
			apd_set_pprof_trace();
		}
		
		$this->start_session( 'Overall' );
	}
	
	
	function start_session( $name, $desc='' )
	{
		$x = array_push( $this->timer_stack, $this->cur_timer );
		
		if( isset($this->timer_stack[ $x - 1 ]) )
		{
			$this->_suspend_timer( $this->timer_stack[ $x - 1 ] );
		}
		
		$this->start_time[ $name ]		= $this->get_micro_seconds();
		$this->start_memory[ $name ]	= $this->get_current_memory();
		
		$this->cur_timer				= $name;
		
		$this->descriptions[ $name ]	= $desc;
		
		if( array_key_exists( $name, $this->internal_count ) )
		{
			$this->internal_count[ $name ]++;
		}
		else
		{
			$this->internal_count[ $name ] = 1;
		}
		
		$this->stack_trace				.= "Started Session '{$name}'\n";
		
		$backtrace				= debug_backtrace();
		
		if( is_array($backtrace) AND count($backtrace) )
		{
			$cnt = 0;
			
			foreach( array_reverse( $backtrace ) as $trace )
			{
				$cnt++;

				$this->stack_trace	.= "\t{$cnt}. Function '{$trace['function']}' in {$trace['file']} (line {$trace['line']})\n";
			}
		}
	}
	
	
	function stop_session( $name )
	{
		$this->end_time[ $name ]			= $this->get_micro_seconds();
		$this->end_memory[ $name ]			= $this->get_current_memory();
		
        if( !array_key_exists( $name, $this->running_time ) )
        {
            $this->running_time[ $name ] 	= $this->get_elapsed_time( $name );
        }
        else
        {
            $this->running_time[ $name ] 	+= $this->get_elapsed_time( $name );
        }
        
        $this->cur_timer					= array_pop( $this->timer_stack );
        
		$this->stack_trace				.= "Stopped Session '{$name}'\n";
		
        if( isset($this->cur_timer) )
        {
        	$this->_resume_timer( $this->cur_timer );
    	}
	}
	
	
	
	function get_elapsed_time( $name )
	{
		if( !array_key_exists( $name, $this->start_time ) )
		{
			return 0;
		}
		
		if( array_key_exists( $name, $this->end_time ) )
		{
			return ( $this->end_time[ $name ] - $this->start_time[ $name ] );
		}
		else
		{
			return( $this->get_micro_seconds() - $this->start_time[ $name ] );
		}
	}
	
	
	function get_total_time()
	{
		return ( $this->get_micro_seconds() - $this->init_time );
	}
	
	
	function get_peak_memory()
	{
		if( function_exists( 'memory_get_peak_usage' ) )
		{
			$this->peak_memory = memory_get_peak_usage();
		}
		else
		{
			if( count( $this->start_memory ) )
			{
				foreach( $this->start_memory as $mem_entry )
				{
					if( $mem_entry > $this->peak_memory )
					{
						$this->peak_memory = $mem_entry;
					}
				}
			}
			
			if( count( $this->end_memory ) )
			{
				foreach( $this->end_memory as $mem_entry )
				{
					if( $mem_entry > $this->peak_memory )
					{
						$this->peak_memory = $mem_entry;
					}
				}
			}
		}
		
		return $this->peak_memory;
	}

	
	function _resume_timer( $name )
	{
		$this->stack_trace			.= "Resumed Session '{$name}'\n";
		
		$this->start_time[ $name ]	= $this->get_micro_seconds();
	}
	
	
	function _suspend_timer( $name )
	{
		$this->stack_trace			.= "Suspended Session '{$name}'\n";
		
		$this->end_time[ $name ]	= $this->get_micro_seconds();
		
        if( !array_key_exists( $name, $this->running_time ) )
        {
            $this->running_time[ $name ] 	= $this->get_elapsed_time( $name );
        }
        else
        {
            $this->running_time[ $name ] 	+= $this->get_elapsed_time( $name );
        }
    }
	
	
	
	function get_micro_seconds()
	{
		$tmp = explode( " ", microtime() );
		
		return ( $tmp[0] + $tmp[1] );
	}
	
	
	function get_current_memory()
	{
		if( ! function_exists( 'memory_get_usage' ) )
		{
			return 0;
		}
		else
		{
			return memory_get_usage();
		}
	}
	
	
	function format_memory( $val=0 )
	{
		if( $val >= 1048576 )
		{
			return ( round( ( $val / 1048576 * 100 ), 2 ) / 100 ) . 'MB';
		}
		else if( $val >= 1024 )
		{
			return ( round( ( $val / 1024 * 100 ), 2 ) / 100 ) . 'KB';
		}
		else
		{
			return intval( $val ) . 'b';
		}
	}
	
	
    function output_results( $display_backtrace=true, $log=false )
    {
	    $this->stop_session( 'Overall' );
	    $this->total_memory	= $this->get_current_memory();
	    
	    $timed_total		= 0;
	    $percent_total		= 0;
	    $output				= null;
	    
	    $overall_time		= $this->get_total_time();
	    $overall_memory		= $this->get_peak_memory();
	    
	    if( $log == false )
	    {
		    print "<pre>\n";
	    }
	    
	    ksort($this->descriptions);
	    
	    $output  = "============================================================================\n";
	    $output .= "                              PROFILER OUTPUT\n";
	    $output .= "============================================================================\n";
	    $output .= "Calls			Time	Memory		Routine\n";
	    $output .= "----------------------------------------------------------------------------\n";
	    
	    foreach( $this->descriptions as $key => $val )
	    {
		    $this_total		= $this->running_time[$key];
		    $this_count		= $this->internal_count[$key];
		    $this_percent	= ( $this_total / $overall_time ) * 100;
		    $this_memory	= $this->format_memory( $this->end_memory[ $key ] - $this->start_memory[ $key ] );
		    
		    $timed_total	+= $this_total;
		    $percent_total	+= $this_percent;
		    $mem_total		+= $this->end_memory[ $key ] - $this->start_memory[ $key ];
		    
		    $output .= sprintf( "%3d	%3.4f ms (%3.2f %%)  %s	 %s\n", $this_count, $this_total * 1000, $this_percent, $this_memory, $key);
	    }
	    
	    $output .= "\n";
	    
	    $missed_time	= $overall_time - $timed_total;
	    $missed_percent	= ( $missed_time / $overall_time ) * 100;
	    $percent_total	+= $missed_percent;
	    $missed_memory	= $this->format_memory( $this->total_memory - $this->init_memory - $mem_total );
	    
		$output .= sprintf( "   	 %3.4f ms (%3.2f %%)  %s  %s\n", $missed_time * 1000, $missed_percent, $missed_memory, 'Leaked' );
		
		$output .= "============================================================================\n";
		$output .= "Start Memory: " . $this->format_memory( $this->init_memory ) . "\n";
		$output .= "Total Memory: " . $this->format_memory( $this->total_memory ) . "\n";
		$output .= "Peak Memory:  " . $this->format_memory( $this->peak_memory ) . "\n";
		$output .= "============================================================================\n";
		
		$output .= sprintf( "       %3.4f ms (%3.2f %%)  %s  %s\n", $timed_total * 1000, $pecent_total, $this->format_memory( $this->total_memory ), 'OVERALL TIME' );

		$output .= "============================================================================\n";

		if( $display_backtrace == true )
		{
			$output .= "Backtrace\n";
			$output .= $this->stack_trace;
		}
		
		if( $log == false )
		{
			print $output . '</pre>';
		}
		else
		{
			return $output;
		}
    }
	
	
}


?>