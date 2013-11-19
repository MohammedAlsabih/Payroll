<?php
/**
* Shift model
*
* Used in Shift controller
*/
class Shift_model extends CI_Model
{
    /**
    * Insert new record into 'shifts'
    *
    * @param array @info Data to be inserted
    * @return bool True on success
    */
    function save($info)
    {
        if (isset($info['shift_id']) == true) {
            $id = $info['shift_id'];
            unset($info['shift_id']);
            $this->db->where('shift_id', $id);
            $this->db->update('shifts', $info);
        } else {
            $this->db->insert('shifts', $info);
        }

        if ($this->db->affected_rows() == 1) {
            return true;
        }

        return false;
    }

    /**
    * Creates detaild listing of working hours
    *
    * @param array $shift_data Array containing 'date', 'start' and 'end'
    * @return array Detaild hour listing
    */
    function split($shift_data)
    {
        // List of holidays
        $holidays = array(
            "01.01." => "Nova Godina",
            "06.01." => "Bogojavljanje ili Sveta tri kralja",
            "01.05." => "Praznik rada",
            "22.06." => "Dan antifašističke borbe",
            "25.06." => "Dan državnosti",
            "05.08." => "Dan domovinske zahvalnosti",
            "15.08." => "Velika Gospa",
            "08.10." => "Dan neovisnosti",
            "01.11." => "Dan svih svetih",
            "25.12." => "Božić",
            "26.12." => "Sveti Stjepan",
            date("d.m.", easter_date()) => "Uskrs",
            date("d.m.", strtotime('+1 day', easter_date())) => "Uskršnji ponedjeljak",
            date("d.m.", strtotime('+60 days', easter_date())) => "Tijelovo"
        );

        // Beginning and ending timestamp
        $shift = new DateTime($shift_data['date'] . ' ' . $shift_data['start'] . ':00');
        $shift_end = new DateTime($shift_data['date'] . ' ' . $shift_data['end'] . ':00');
        if ($shift >= $shift_end) { // ended on next day
            $shift_end->modify('+1 day');
        }

        // Reset shift details
        $shift_data = array(
            'day' => 0,
            'night' => 0,
            'sunday' => 0,
            'sunday_night' => 0,
            'holiday' => 0,
            'holiday_night' => 0,
        );

        while ($shift < $shift_end) { // iterate through shift
            $hour = intval($shift->format('H')); // grab current hour

            if ($hour < 6 || $hour >= 22) {
                // night hour
                if (array_key_exists($shift->format('d.m.'), $holidays)) {
                    $shift_data['holiday_night']++;
                } else if ($shift->format('N') == '7') {
                    $shift_data['sunday_night']++;
                } else {
                    $shift_data['night']++;
                }
            } else {
                // day hour
                if (array_key_exists($shift->format('d.m.'), $holidays)) {
                    $shift_data['holiday']++;
                } else if ($shift->format('N') == '7') {
                    $shift_data['sunday']++;
                } else {
                    $shift_data['day']++;
                }
            }

            $shift->modify('+1 hour'); // move to next hour
        }

        return $shift_data;  
    }

    /**
    * Retrieve rates from database
    *
    * @param int $rate_id ID of rate in database
    * @return array Rate values from database
    */
    function get_rates($rate_id)
    {
        $query = $this->db->get_where('rates', array('rate_id' => $rate_id));
        
        return $query->row_array();
    }

    /**
     * Calculates total income
     *
     * @param array $user_rate Array returned by get_rates()
     * @param array $shift_data Array returned by split()
     * @return string Number formated to 2 decimal places
     */
    function calculate($user_rate, $shift_data)
    {
        $rates = $this->get_rates($user_rate);

        $total = 0;
        foreach ($shift_data as $key => $value) {
            $total += $rates[$key] * $value;
        }

        return number_format($total, 2);
    }

    /**
    * Generate report
    *
    * @param string $start Beginning date
    * @param string $end Ending date
    * @return array report in form of array
    */
    function generate_report($start, $end, $user_id)
    {
        // get shift details
        $this->db->where("`date` BETWEEN '{$start}' AND '{$end}'");
        $this->db->where('user_id_fk', $user_id);
        $this->db->order_by('date', 'asc');
        $query = $this->db->get('shifts');
        $shifts = $query->result_array();

        // get totals
        $this->db->select_sum('total')->select_sum('bonus')
            ->select_sum('day')->select_sum('night')
            ->select_sum('sunday')->select_sum('sunday_night')
            ->select_sum('holiday')->select_sum('holiday_night');
        $this->db->where("`date` BETWEEN '{$start}' AND '{$end}'");
        $this->db->where('user_id_fk', $user_id);
        $query = $this->db->get('shifts');
        $totals = $query->row_array();

        $shifts['total'] = $totals;

        return $shifts;
    }

    /**
    * Delete shift from db
    *
    * @param int $id Shift ID number
    * @return bool True on success, otherwise false
    */
    function delete($shift_id)
    {
        $this->db->where('shift_id', $shift_id);
        $this->db->delete('shifts');

        if ($this->db->affected_rows() == 1) {
            return true;
        }

        return false;
    }
}