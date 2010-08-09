// gets the irr of a solar system
function IRR(sys_size,sys_output,sys_derate,sys_cost,sys_inc,sys_utility,sys_inverter,sys_maintenance,sys_liability,sys_life) {
	// setup
	var sys_cost_amortized = sys_cost / sys_life;
	var sys_inverter_reduced = sys_inverter * (1 - 0.42);
	var sys_inverter_amortized = sys_inverter_reduced / sys_life;
	var cashflows = [];
	// get all cashflows
	for(var i=0;i<sys_life;i++) {
		var cf = new _CashFlow(i);
		cashflows.push(cf);
	}
	// get irrs
	this.irr_after_tax = _getIRR(cashflows,-sys_cost,true);
	this.irr_pre_tax = _getIRR(cashflows,-sys_cost,false);
	this.cashflows = cashflows;
	// make an annual cashflow
	function _CashFlow(n) {
		this.output = i > 0 ? cashflows[n-1].output * (1 - sys_derate) : sys_output;
		this.utility = i > 0 ? cashflows[n-1].utility * (1 + sys_inc / 100) : sys_utility;
		this.cost_w_utility = this.utility * this.output;
		this.cum_utility_cost = i > 0 ? cashflows[n-1].cum_utility_cost + this.cost_w_utility : this.cost_w_utility;
		this.solar_rate = (sys_cost_amortized + sys_inverter_amortized) / this.output;
		this.cost_w_solar = this.output * this.solar_rate;
		this.cum_solar_cost = i > 0 ? cashflows[n-1].cum_solar_cost + this.cost_w_solar : this.cost_w_solar;
		this.after_tax_revenue = this.cost_w_utility - sys_inverter_amortized - sys_maintenance;
		this.pre_tax_revenue = this.after_tax_revenue / (1 - sys_liability / 100);
		this.solar_cum_revenue = i > 0 ? cashflows[n-1].solar_cum_revenue + this.pre_tax_revenue : this.pre_tax_revenue;
		this.roi = this.after_tax_revenue / sys_cost;
	}
}
// calculates an irr
function _getIRR(cashFlows,startUp,afterTax,estimatedResult) {
	var cfs = []
	cfs.push(startUp);
	for(cf in cashFlows) afterTax ? cfs.push(cashFlows[cf].after_tax_revenue) : cfs.push(cashFlows[cf].pre_tax_revenue);
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