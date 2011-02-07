<?php
###############
# DECLARATION #
###############
// financial analysis of a solar system
class SolarAnalysis {
	#####################
	# PUBLIC PROPERTIES #
	#####################
	// inputs
	public $sys_size;
	public $sys_output;
	public $sys_derate;
	public $sys_cost;
	public $sys_inc;
	public $sys_utility;
	public $sys_life;
	public $sys_usage;
	public $sys_sorec;
	public $sys_sorec_yrs;
	public $sys_tax_brac;
	public $sys_inverter;
	// outputs
	public $sys_inverter_half_life;
	public $total_output;
	public $total_savings;
	public $total_elec_bill_no_solar;
	public $total_elec_bill_solar;
	public $total_sorec_rev;
	public $total_profit;
	public $payback_yrs;
	public $annual_data;
	public $lcoe_solar_energy;
	public $elec_savings;
	public $avg_year_savings;
	public $avg_month_savings;
	// results
	public $irr_post_tax;
	public $irr_pre_tax;
	public $total_life_cycle_payback;
	##################
	# PUBLIC METHODS #
	##################
	// constructor
	public function __construct($sys_size,$sys_output,$sys_derate,$sys_cost,$sys_inc,$sys_utility,$sys_life,$sys_usage,$sys_sorec,$sys_sorec_yrs,$sys_tax_brac,$sys_inverter) {
		// setup args
		$this->sys_size = $sys_size;
		$this->sys_output = $sys_output;
		$this->sys_derate = $sys_derate;
		$this->sys_cost = $sys_cost;
		$this->sys_inc = $sys_inc;
		$this->sys_utility = $sys_utility;
		$this->sys_life = $sys_life;
		$this->sys_usage = $sys_usage;
		$this->sys_sorec = $sys_sorec;
		$this->sys_sorec_yrs = $sys_sorec_yrs;
		$this->sys_tax_brac = $sys_tax_brac;
		$this->sys_inverter = $sys_inverter;
		// vars
		$this->sys_inverter_half_life = $this->sys_inverter > 10000 ? 0.2 * $this->sys_inverter : 0.6 * $this->sys_inverter;
		$this->total_output = 0;
		$this->total_savings = 0;
		$this->total_elec_bill_no_solar = 0;
		$this->total_elec_bill_solar = 0;
		$this->total_sorec_rev = 0;
		$this->total_profit = 0;
		$this->payback_yrs = 1;
		$this->annual_data = array();
	}
	public function finish() {
		// outputs
		$this->lcoe_solar_energy = ($this->sys_cost + $this->sys_inverter_half_life) / $this->total_output;
		$this->elec_savings = $this->annual_data[count($this->annual_data)-1]->cum_savings_solar;
		$this->avg_year_savings = $this->total_savings / $this->sys_life;
		$this->avg_month_savings = $this->avg_year_savings / 12;
		// results
		$this->irr_post_tax = $this->_irr(-$this->sys_cost,NULL);
		$this->irr_pre_tax = $this->_irr(-$this->sys_cost,NULL,FALSE);
		$this->total_life_cycle_payback = $this->elec_savings / $this->sys_cost;
	}
	// calculates an irr
	private function _irr($startUp,$estimatedResult,$postTax=TRUE) {
		$cfs = array();
		$cfs[] = $startUp;
		foreach($this->annual_data as $sy) $cfs[] = $postTax ? $sy->irr_post_tax : $sy->irr_pre_tax;
	    $result = 0;
	    if($cfs && count($cfs)>0) { 
	        if($cfs[0] != 0.0) {
				// check
	            $noOfCashFlows = count($cfs);
	            $sumCashFlows = 0.0;
	            $noOfNegativeCashFlows = 0;
	            $noOfPositiveCashFlows = 0;
	            for($i=0;$i<$noOfCashFlows;$i++) {
	                $sumCashFlows += $cfs[$i];
	                if($cfs[$i]>0) $noOfPositiveCashFlows++;
	                else if($cfs[$i]<0) $noOfNegativeCashFlows++;
	            }
	            if($noOfNegativeCashFlows>0 && $noOfPositiveCashFlows>0) {
	                $irrGuess = 0.1;
	                if($estimatedResult) {
	                    $irrGuess = $estimatedResult;
	                    if($irrGuess<=0.0) $irrGuess = 0.5;
	                }
	                $irr = 0.0;
	                if($sumCashFlows<0) $irr = -$irrGuess;
	                else $irr = $irrGuess;
					// iterate
	                $minDistance = 0.0000001;
	                $cashFlowStart = $cfs[0];
	                $maxIteration = 50;
	                $wasHi = FALSE;
	                $cashValue = 0.0;
	                for($i=0;$i<=$maxIteration;$i++) {
	                    $cashValue = $cashFlowStart;
	                    for($j=1;$j<$noOfCashFlows;$j++) $cashValue += $cfs[$j] / pow(1.0+$irr,$j);
	                    if(abs($cashValue)<0.01) { $result = $irr; break; }
	                    if($cashValue>0.0) {
	                        if($wasHi) $irrGuess /= 2;
	                        $irr += $irrGuess;
	                        if($wasHi) {
	                            $irrGuess -= $minDistance;
	                            $wasHi = FALSE;
	                        }
	                    } else {
	                        $irrGuess /= 2;
	                        $irr -= $irrGuess;
	                        $wasHi = TRUE;
	                    }
	                    if($irrGuess<=$minDistance) {
	                        $result = $irr;
	                        break;
	                    }
	                }
	            }
	        }
	    }
	    return $result;
	}
} # CLASS >>
###############
# DECLARATION #
###############
// build annual solar figures
class SolarYear {
	#####################
	# PUBLIC PROPERTIES #
	#####################
	public $utility;
	public $elec_bill_no_solar;
	public $cum_elec_bill_no_solar;
	public $output;
	public $solar_savings;
	public $sorec_rev;
	public $total_solar_value;
	public $inverter_replacement;
	public $cum_savings_solar;
	public $elec_bill_solar;
	public $cum_bill_solar;
	public $irr_post_tax;
	public $irr_pre_tax;
	public $cashflow;
	public $simple_payback;
	public $simple_payback_sorec;
	##################
	# PUBLIC METHODS #
	##################
	// constructor
	public function __construct($a,$n) {
		// vars
		$this->utility = $n > 0 ? $a->annual_data[$n-1]->utility * (1 + $a->sys_inc) : $a->sys_utility;
		$this->elec_bill_no_solar = $this->utility * $a->sys_usage;
		$this->cum_elec_bill_no_solar = $n > 0 ? $a->annual_data[$n-1]->cum_elec_bill_no_solar + $this->elec_bill_no_solar : $this->elec_bill_no_solar;
		$this->output = $n > 0 ? $a->annual_data[$n-1]->output * (1 - $a->sys_derate) : $a->sys_output;
		$this->solar_savings = $this->output * $this->utility;
		$this->sorec_rev = $n + 1 > $a->sys_sorec_yrs ? 0 : -$this->output * $a->sys_sorec;
		$this->total_solar_value = $this->solar_savings - $this->sorec_rev;
		$this->inverter_replacement = $n + 1 == floor($a->sys_life / 2) ? $a->sys_inverter_half_life : 0;
		$this->cum_savings_solar = $n > 0 ? $a->annual_data[$n-1]->cum_savings_solar + $this->total_solar_value : $this->total_solar_value;
		$this->elec_bill_solar = $this->elec_bill_no_solar - $this->total_solar_value;
		$this->cum_bill_solar = $n > 0 ? $a->annual_data[$n-1]->cum_bill_solar + $this->elec_bill_solar : $this->elec_bill_solar;
		$this->irr_post_tax = $this->total_solar_value;
		$this->irr_pre_tax = $this->irr_post_tax / (1-$a->sys_tax_brac);
		$this->cashflow = $n > 0 ? $a->annual_data[$n-1]->cashflow + $this->total_solar_value - $this->inverter_replacement: $this->total_solar_value - $a->sys_cost;
		$this->simple_payback = $this->cashflow > 0 ? 0 : 1;
		//$this->simple_payback_sorec = $n > 0 ? $a->annual_data[$n-1]->simple_payback_sorec + $this->inverter_replacement + $this->sorec_rev : $a->sys_cost + $this->inverter_replacement + $this->sorec_rev;
	}
} # CLASS >>
?>