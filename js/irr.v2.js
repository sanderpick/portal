/*--------------------------------------------------------------------------.
|  Software: Einstein Financials	                                  		|
|   Version: 2.0                                                            |
|   Contact: spick@cleanenergysolutionsinc.com 								|                
| ------------------------------------------------------------------------- |
|     Admin: Sander Pick (project admininistrator)                          |
|   Authors: Sander Pick                         							|
| Copyright (c) 20010-Today Lighthouse Solar. All Rights Reserved.       	|                     
| ------------------------------------------------------------------------- |
|   License: By downloading or copying any part of this file,	 			|
|			 you agree to the following: 									|
|			* The product may not be used for commercial projects. 			|
|			* You are not free to remove the copyright information.			|
| 			* You are not free to use or copy any of this file.             |
'--------------------------------------------------------------------------*/
// gets the irr of a solar system
function IRR(sys_size,sys_output,sys_derate,sys_cost,sys_inc,sys_utility,sys_life,sys_maintenance,sys_usage,sys_sorec,sys_sorec_yrs,sys_inverter) {
	// vars
	var cashflows = [];
	var sys_inverter_half_life = 0.6 * sys_inverter;
	var total_output = 0;
	var total_savings = 0;
	// build cashflows
	for(var i=0;i<sys_life;i++) {
		var cf = new _CashFlow(i);
		cashflows.push(cf);
	}
	// outputs
	this.lcoe_solar_energy = (sys_cost + sys_inverter_half_life) / total_output;
	this.elec_savings = cashflows[cashflows.length - 1].cum_savings_solar;
	this.avg_year_savings = total_savings / sys_life;
	this.avg_month_savings = this.avg_year_savings / 12;
	// results
	this.irr_tax_free = _getIRR(cashflows,-sys_cost);
	this.total_life_cycle_payback = this.elec_savings / sys_cost;
	// save for graphs
	this.cashflows = cashflows;
	// make an annual cashflow
	function _CashFlow(n) {
		// vars
		this.utility = i > 0 ? cashflows[n-1].utility * (1 + sys_inc) : sys_utility;
		this.elec_bill_no_solar = this.utility * sys_usage;
		this.cum_elec_bill_no_solar = i > 0 ? cashflows[n-1].cum_elec_bill_no_solar + this.elec_bill_no_solar : this.elec_bill_no_solar;
		this.output = i > 0 ? cashflows[n-1].output * (1 - sys_derate) : sys_output;
		this.solar_savings = this.output * this.utility;
		this.sorec_rev = i + 1 > sys_sorec_yrs ? 0 : -this.output * sys_sorec;
		this.total_solar_value = this.solar_savings - this.sorec_rev;
		this.inverter_replacement = i + 1 == Math.floor(sys_life / 2) ? sys_inverter_half_life : 0;
		this.cum_savings_solar = i > 0 ? cashflows[n-1].cum_savings_solar + this.total_solar_value : this.total_solar_value;
		this.elec_bill_solar = this.elec_bill_no_solar - this.total_solar_value;
		this.cum_bill_solar = i > 0 ? cashflows[n-1].cum_bill_solar + this.elec_bill_solar : this.elec_bill_solar;
		this.irr = this.total_solar_value;
		this.cashflow = i > 0 ? cashflows[n-1].cashflow + this.irr : this.irr - sys_cost;
		this.simple_payback = i + 1 < Math.floor(sys_life / 2) ? sys_cost : sys_inverter_half_life + sys_cost;
		this.simple_payback_sorec = i > 0 ? cashflows[n-1].simple_payback_sorec + this.inverter_replacement + this.sorec_rev : sys_cost + this.inverter_replacement + this.sorec_rev;
		// sums
		total_output += this.output;
		total_savings += this.solar_savings;
	}
}
// calculates an irr
function _getIRR(cashFlows,startUp,estimatedResult) {
	var cfs = []
	cfs.push(startUp);
	for(cf in cashFlows) cfs.push(cashFlows[cf].irr);
    var result;
    if(cfs && cfs.length>0) { 
        if(cfs[0] != 0.0) {
			// check
            var noOfCashFlows = cfs.length;
            var sumCashFlows = 0.0;
            var noOfNegativeCashFlows = 0;
            var noOfPositiveCashFlows = 0;
            for(var i=0;i<noOfCashFlows;i++) {
                sumCashFlows += cfs[i];
                if(cfs[i]>0) noOfPositiveCashFlows++;
                else if(cfs[i]<0) noOfNegativeCashFlows++;
            }
            if(noOfNegativeCashFlows>0 && noOfPositiveCashFlows>0) {
                var irrGuess = 0.1;
                if(estimatedResult) {
                    irrGuess = estimatedResult;
                    if(irrGuess<=0.0) irrGuess = 0.5;
                }
                var irr = 0.0;
                if(sumCashFlows<0) irr = -irrGuess;
                else irr = irrGuess;
				// iterate
                var minDistance = 0.0000001;
                var cashFlowStart = cfs[0];
                var maxIteration = 50;
                var wasHi = false;
                var cashValue = 0.0;
                for(var i=0;i<=maxIteration;i++) {
                    cashValue = cashFlowStart;
                    for(var j=1;j<noOfCashFlows;j++) cashValue += cfs[j] / Math.pow(1.0 + irr, j);
                    if(Math.abs(cashValue)<0.01) { result = irr; break; }
                    if(cashValue>0.0) {
                        if(wasHi) irrGuess /= 2;
                        irr += irrGuess;
                        if(wasHi) {
                            irrGuess -= minDistance;
                            wasHi = false;
                        }
                    } else {
                        irrGuess /= 2;
                        irr -= irrGuess;
                        wasHi = true;
                    }
                    if(irrGuess<=minDistance) {
                        result = irr;
                        break;
                    }
                }
            }
        }
    }
    return result;
}