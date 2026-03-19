<?php
/**
* Calendar Generation Class
*
* This class provides a simple reuasable means to produce month calendars in valid html
*
* @version 2.8
* @author Jim Mayes <jim.mayes@gmail.com>
* @link http://style-vs-substance.com
* @copyright Copyright (c) 2008, Jim Mayes
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt GPL v2.0
*/



class Calendar{
	var $date;
	var $year;
	var $month;
	var $day;
	
	var $maanden;
	var $dagen;	
	
	var $week_start_on = FALSE;
	var $week_start = 1;// sunday
	
	var $link_days = TRUE;
	var $link_to;
	var $formatted_link_to;
	
	var $mark_today = TRUE;
	var $today_date_class = 'today';
	
	var $mark_selected = TRUE;
	var $selected_date_class = 'selected';
	
	var $mark_passed = TRUE;
	var $passed_date_class = 'passed';
	
	var $highlighted_dates;
	var $highlighted_dates1;
	var $highlighted_dates2;
	var $highlighted_dates3;
	var $highlighted_dates4;
	var $highlighted_dates5;
	var $highlighted_dates6;
	var $highlighted_dates7;
    
    var $highlighted_dates8;
    var $highlighted_dates9;
    var $highlighted_dates10;
    var $highlighted_dates11;
	
	var $default_highlighted_class = 'highlighted';
	var $default_highlighted_class1 = 'highlighted1';
	var $default_highlighted_class2 = 'highlighted2';
	var $default_highlighted_class3 = 'highlighted3';
	var $default_highlighted_class4 = 'highlighted4';
	var $default_highlighted_class5 = 'highlighted5';
	var $default_highlighted_class6 = 'highlighted6';
	var $default_highlighted_class7 = 'highlighted7';
    var $default_highlighted_class8 = 'highlighted8';
    
    // cus interventies
    var $default_highlighted_class9 = 'highlighted9';
    
    // sanitair
    var $default_highlighted_class10 = 'highlighted10';
    
    // permanentie
    var $default_highlighted_class11 = 'highlighted11';

	/* CONSTRUCTOR */
	function Calendar($date = NULL, $year = NULL, $month = NULL){
		$self = htmlspecialchars($_SERVER['PHP_SELF']);
		$this->link_to = $self;
		
		if( is_null($year) || is_null($month) ){
			if( !is_null($date) ){
				//-------- strtotime the submitted date to ensure correct format
				$this->date = date("Y-m-d", strtotime($date));
			} else {
				//-------------------------- no date submitted, use today's date
				$this->date = date("Y-m-d");
			}
			$this->set_date_parts_from_date($this->date);
		} else {
			$this->year		= $year;
			$this->month	= str_pad($month, 2, '0', STR_PAD_LEFT);
		}	
	}
	
	function set_date_parts_from_date($date){
		$this->year		= date("Y", strtotime($date));
		$this->month	= date("m", strtotime($date));
		$this->day		= date("d", strtotime($date));
	}
	
	function day_of_week($date){
		$day_of_week = date("N", $date);
		if( !is_numeric($day_of_week) ){
			$day_of_week = date("w", $date);
			if( $day_of_week == 0 ){
				$day_of_week = 7;
			}
		}
		return $day_of_week;
	}
	
	function output_calendar($year = NULL, $month = NULL, $calendar_class = 'calendar')
	{
		$maanden[1] = "Januari";
		$maanden[2] = "Februari";
		$maanden[3] = "Maart";
		$maanden[4] = "April";
		$maanden[5] = "Mei";
		$maanden[6] = "Juni";
		$maanden[7] = "Juli";
		$maanden[8] = "Augustus";
		$maanden[9] = "September";
		$maanden[10] = "Oktober";
		$maanden[11] = "November";
		$maanden[12] = "December";
		
		$dagen[1] = "Maandag";
		$dagen[2] = "Dinsdag";
		$dagen[3] = "Woensdag";
		$dagen[4] = "Donderdag";
		$dagen[5] = "Vrijdag";
		$dagen[6] = "Zaterdag";
		$dagen[7] = "Zondag";
		
		if( $this->week_start_on !== FALSE ){
			echo "The property week_start_on is replaced due to a bug present in version before 2.6. of this class! Use the property week_start instead!";
			exit;
		}
		
		//--------------------- override class methods if values passed directly
		$year = ( is_null($year) )? $this->year : $year;
		$month = ( is_null($month) )? $this->month : str_pad($month, 2, '0', STR_PAD_LEFT);
	
		//------------------------------------------- create first date of month
		$month_start_date = strtotime($year . "-" . $month . "-01");
		//------------------------- first day of month falls on what day of week
		$first_day_falls_on = $this->day_of_week($month_start_date);
		//----------------------------------------- find number of days in month
		$days_in_month = date("t", $month_start_date);
		//-------------------------------------------- create last date of month
		$month_end_date = strtotime($year . "-" . $month . "-" . $days_in_month);
		//----------------------- calc offset to find number of cells to prepend
		$start_week_offset = $first_day_falls_on - $this->week_start;
		$prepend = ( $start_week_offset < 0 )? 7 - abs($start_week_offset) : $first_day_falls_on - $this->week_start;
		//-------------------------- last day of month falls on what day of week
		$last_day_falls_on = $this->day_of_week($month_end_date);

		//------------------------------------------------- start table, caption
		$output  = "<table class=\"" . $calendar_class . "\">\n";
		//$output .= "<caption>" . ucfirst(strftime("%B %Y", $month_start_date)) . "" . date('n', $month_start_date) . "</caption>\n";
		
        if( isset( $_GET['date'] ) )
        {
            $output .= "<caption><a href='kalender.php?month=".date('m', $month_start_date)."&year=".$year."'>" . $maanden[ date('n', $month_start_date) ] . " " . substr($_GET['date'],0,4 ) . "</a></caption>\n";    
        }else
        {
            $output .= "<caption><a href='kalender.php?month=".date('m', $month_start_date)."&year=".$year."'>" . $maanden[ date('n', $month_start_date) ] . "</a></caption>\n";
        }
        
		$col = '';
		$th = '';
		for( $i=1,$j=$this->week_start,$t=(3+$this->week_start)*86400; $i<=7; $i++,$j++,$t+=86400 ){
			//$localized_day_name = gmstrftime('%A',$t);
			
			$localized_day_name = $dagen[date('N',$t)];
			
			$col .= "<col class=\"" . strtolower($localized_day_name) ."\" />\n";
			$th .= "\t<th title=\"" . ucfirst($localized_day_name) ."\">" . strtoupper($localized_day_name[0]) ."</th>\n";
			$j = ( $j == 7 )? 0 : $j;
		}
		
		//------------------------------------------------------- markup columns
		$output .= $col;
		
		//----------------------------------------------------------- table head
		$output .= "<thead>\n";
		$output .= "<tr><th>&nbsp;</th>\n";
		
		$output .= $th;
		
		$output .= "</tr>\n";
		$output .= "</thead>\n";
		
		//---------------------------------------------------------- start tbody
		$output .= "<tbody>\n";
		$output .= "<tr>\n";
		
		//---------------------------------------------- initialize week counter
		$weeks = 1;
		
		//--------------------------------------------------- pad start of month
		
		//------------------------------------ adjust for week start on saturday
		
		
		
		$output .= "\t<td><b>". date('W', mktime( 0, 0, 0, $month, 1, $year) ) ."</b></td>\n";
		
		for($i=1;$i<=$prepend;$i++){
			$output .= "\t<td class=\"pad\">&nbsp;</td>\n";
		}
		
		//--------------------------------------------------- loop days of month
		for($day=1,$cell=$prepend+1; $day<=$days_in_month; $day++,$cell++){
			
			/*
			if this is first cell and not also the first day, end previous row
			*/
			if( $cell == 1 && $day != 1 ){
				$output .= "<tr>\n";
			}
			
			//-------------- zero pad day and create date string for comparisons
			$day = str_pad($day, 2, '0', STR_PAD_LEFT);
			$day_date = $year . "-" . $month . "-" . $day;
			
			//-------------------------- compare day and add classes for matches
			if( $this->mark_today == TRUE && $day_date == date("Y-m-d") ){
				$classes[] = $this->today_date_class;
			}
			
			if( $this->mark_selected == TRUE && $day_date == $this->date ){
				$classes[] = $this->selected_date_class;
			}
			
			if( $this->mark_passed == TRUE && $day_date < date("Y-m-d") ){
				$classes[] = $this->passed_date_class;
			}
			
			if( is_array($this->highlighted_dates) ){
				if( in_array($day_date, $this->highlighted_dates) ){
					$classes[] = $this->default_highlighted_class;
				}
			}
			
			if( is_array($this->highlighted_dates1) ){
				if( in_array($day_date, $this->highlighted_dates1) ){
					$classes[] = $this->default_highlighted_class1;
				}
			}
			
			if( is_array($this->highlighted_dates2) ){
				if( in_array($day_date, $this->highlighted_dates2) ){
					$classes[] = $this->default_highlighted_class2;
				}
			}
			
			if( is_array($this->highlighted_dates4) ){
				if( in_array($day_date, $this->highlighted_dates4) ){
					$classes[] = $this->default_highlighted_class4;
				}
			}
			
			if( is_array($this->highlighted_dates5) ){
				if( in_array($day_date, $this->highlighted_dates5) ){
					$classes[] = $this->default_highlighted_class5;
				}
			}
			
			if( is_array($this->highlighted_dates6) ){
				if( in_array($day_date, $this->highlighted_dates6) ){
					$classes[] = $this->default_highlighted_class6;
				}
			}
			
			if( is_array($this->highlighted_dates7) ){
				if( in_array($day_date, $this->highlighted_dates7) ){
					$classes[] = $this->default_highlighted_class7;
				}
			}
            
            if( is_array($this->highlighted_dates8) ){
				if( in_array($day_date, $this->highlighted_dates8) ){
					$classes[] = $this->default_highlighted_class8;
				}
			}
            
            if( is_array($this->highlighted_dates9) ){
				if( in_array($day_date, $this->highlighted_dates9) ){
					$classes[] = $this->default_highlighted_class9;
				}
			}
            
            if( is_array($this->highlighted_dates10) ){
				if( in_array($day_date, $this->highlighted_dates10) ){
					$classes[] = $this->default_highlighted_class10;
				}
			}
            
            if( is_array($this->highlighted_dates11) ){
				if( in_array($day_date, $this->highlighted_dates11) ){
					$classes[] = $this->default_highlighted_class11;
				}
			}
			
			// hier zitten de dubbels in
			if( is_array($this->highlighted_dates3) ){
				if( in_array($day_date, $this->highlighted_dates3) ){
					$classes[] = $this->default_highlighted_class3;
				}
			}

			// begin add weeknr
			if( $cell == 1 && $day != 1 ){
				$output .= "<td><b>". date('W', mktime( 0, 0, 0, $month, $day, $year) ) ."</b></td>";
			}
			
			// end add weeknr
			
			//----------------- loop matching class conditions, format as string
			if( isset($classes) ){
				$day_class = ' class="';
				foreach( $classes AS $value ){
					$day_class .= $value . " ";
				}
				$day_class = substr($day_class, 0, -1) . '"';
			} else {
				$day_class = '';
			}
			
			//---------------------------------- start table cell, apply classes
			// detect windows os and substitute for unsupported day of month modifer
			$title_format = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')? "%A, %B %#d, %Y": "%A, %B %e, %Y";
			
			$output .= "\t<td" . $day_class . " title=\"" . ucwords(strftime($title_format, strtotime($day_date))) . "\">";
			
			//----------------------------------------- unset to keep loop clean
			unset($day_class, $classes);
			
			//-------------------------------------- conditional, start link tag 
			switch( $this->link_days ){
				case 0 :
					$output .= $day;
				break;
				
				case 1 :
					if( empty($this->formatted_link_to) ){
						$output .= "<a href=\"" . $this->link_to . "?date=" . $day_date . "\">" . $day . "</a>";
					} else {
						$output .= "<a href=\"" . strftime($this->formatted_link_to, strtotime($day_date)) . "\">" . $day . "</a>";
					}
				break;
				
				case 2 :
					if( is_array($this->highlighted_dates) ){
						if( in_array($day_date, $this->highlighted_dates) ){
							if( empty($this->formatted_link_to) ){
								$output .= "<a href=\"" . $this->link_to . "?date=" . $day_date . "\">";
							} else {
								$output .= "<a href=\"" . strftime($this->formatted_link_to, strtotime($day_date)) . "\">";
							}
						}
					}
					
					$output .= $day;
					
					if( is_array($this->highlighted_dates) ){
						if( in_array($day_date, $this->highlighted_dates) ){
							if( empty($this->formatted_link_to) ){
								$output .= "</a>";
							} else {
								$output .= "</a>";
							}
						}
					}
				break;
			}
			
			//------------------------------------------------- close table cell
			$output .= "</td>\n";
			
			//------- if this is the last cell, end the row and reset cell count
			if( $cell == 7 ){
				$output .= "</tr>\n";
				$cell = 0;
			}
			
		}
		
		//----------------------------------------------------- pad end of month
		if( $cell > 1 ){
			for($i=$cell;$i<=7;$i++){
				$output .= "\t<td class=\"pad\">&nbsp;</td>\n";
			}
			$output .= "</tr>\n";
		}
		
		//--------------------------------------------- close last row and table
		$output .= "</tbody>\n";
		$output .= "</table>\n";
		
		//--------------------------------------------------------------- return
		return $output;
		
	}
	
}
?>