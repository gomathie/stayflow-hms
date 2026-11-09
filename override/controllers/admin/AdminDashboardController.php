<?php
/**
 * Keeps the dashboard charts on a period that includes today, unless the
 * employee has chosen Day-1, Month-1 or Year-1. Check-in and the desk lists
 * are unchanged: they always use the calendar day.
 */
class AdminDashboardController extends AdminDashboardControllerCore
{
    public function renderView()
    {
        $this->keepChartsOnAUsefulPeriod();

        return parent::renderView();
    }

    /**
     * A saved range such as last September stays on screen forever, because the
     * daily reset only runs when this browser has not opened the dashboard yet
     * today. Day is the chart button for today, so a range that no longer
     * includes today is brought back to Day. Month and Year that still include
     * today are left as they are.
     */
    protected function keepChartsOnAUsefulPeriod()
    {
        $employee = $this->context->employee;
        if (!$employee || !(int) $employee->id) {
            return;
        }
        $preselect = (string) $employee->preselect_date_range;
        if (in_array($preselect, array('prev-day', 'prev-month', 'prev-year'), true)) {
            return;
        }
        $today = date('Y-m-d');
        $from = (string) $employee->stats_date_from;
        $to = (string) $employee->stats_date_to;
        if ($from !== '' && $to !== '' && $from <= $today && $to >= $today) {
            return;
        }
        $employee->stats_date_from = $today;
        $employee->stats_date_to = $today;
        $employee->preselect_date_range = 'day';
        $employee->update();
        $this->context->cookie->stats_date_update = strtotime($today);
        $this->context->cookie->write();
    }
}
