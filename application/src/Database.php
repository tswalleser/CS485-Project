<?php

namespace App;

class Database {
    private static $database = null;

    private static $server = "127.0.0.1:3306";
    private static $username = "root";
    private static $password = "";
    private static $dbname = "cs485_project";

    private $conn;

    private function __construct() {
        $this->conn = new \mysqli(self::$server, self::$username, self::$password, self::$dbname);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public static function getDatabase() {
        if (self::$database === null) {
            self::$database = new self();
        }

        return self::$database;
    }

    public function escape_string($str) {
        return $this->conn->real_escape_string($str);
    }

    public function states_result() {
        return $this->conn->query("SELECT state_id, State_Name FROM State_Summary ORDER BY State_Name");
    }

    //Login Function

    public function verify_login($username, $password) {
        $stmt = $this->conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $result = $result->fetch_assoc();

            if (password_verify($password, $result['password'])) {
                return $result['user_id'];
            }
        }

        $stmt->close();

        return false;
    }

    //Register Functions

    public function username_available($username) {
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $available = $result->num_rows === 0;

        $stmt->close();

        return $available;
    }

    public function register($username, $email, $password) {
        $created_at = (new \DateTime("now"))->format('Y-m-d H:i:s');

        $stmt = $this->conn->prepare("INSERT INTO users (username, password, email, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $password, $email, $created_at);
        
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    //Dashboard Functions

    public function get_values() {
        $basic = [
            $this->conn->query("SELECT COUNT(*) AS count FROM Location")->fetch_assoc()['count'], //Get total number of counties
            $this->conn->query("SELECT COUNT(*) AS count FROM State_Summary")->fetch_assoc()['count'], //Get total number of states (including D.C. and P.R.)
            $this->conn->query("SELECT ROUND(AVG(Median_Household_Income), 0) AS avg FROM Income WHERE Median_Household_Income > 0")->fetch_assoc()['avg'], //Get average median household income across all counties
            $this->conn->query("SELECT ROUND(AVG(Unemployment_Rate_Pct), 2) AS avg FROM Employment WHERE Unemployment_Rate_Pct >= 0")->fetch_assoc()['avg'], //Get average unemployment rate across all counties
            $this->conn->query("SELECT ROUND(AVG(Median_Gross_Rent), 0) AS avg FROM Cost_of_Living WHERE Median_Gross_Rent > 0")->fetch_assoc()['avg'], //Get average median gross rent across all counties
            $this->conn->query("SELECT ROUND(AVG(Pct_Any_Degree) * 100, 1) AS avg FROM Education_Distribution WHERE Pct_Any_Degree IS NOT NULL")->fetch_assoc()['avg'], //Get average percentage of adults with any degree across all counties
        ];

        $lists = [
            $this->conn->query(" 
                SELECT l.County_Name, ss.State_Name, i.Median_Household_Income
                FROM Location l
                JOIN State_Summary ss ON l.state_id = ss.state_id
                JOIN Income i ON l.county_id = i.county_id
                ORDER BY i.Median_Household_Income DESC
                LIMIT 10"),

            $this->conn->query("
                SELECT l.County_Name, ss.State_Name, an.Composite_Opportunity_Score
                FROM Location l
                JOIN State_Summary ss ON l.state_id = ss.state_id
                JOIN Analysis an ON l.county_id = an.county_id
                ORDER BY an.Composite_Opportunity_Score DESC
                LIMIT 10"),

            $this->conn->query("
                SELECT l.County_Name, ss.State_Name, e.Unemployment_Rate_Pct
                FROM Location l
                JOIN State_Summary ss ON l.state_id = ss.state_id
                JOIN Employment e ON l.county_id = e.county_id
                ORDER BY e.Unemployment_Rate_Pct ASC
                LIMIT 10"),
        ];

        return ['basic' => $basic, 'lists' => $lists];
    }

    //Search Functions

    public function search_db($where_clause) {
        $results = [];
        $sql = sprintf("SELECT l.county_id, l.County_Name, ss.State_Name,
                        i.Median_Household_Income, e.Unemployment_Rate_Pct,
                        ed.Pct_Any_Degree, c.Median_Gross_Rent,
                        cb.Affordability_Tier, a2.Median_Age,
                        an.Composite_Opportunity_Score
                        FROM Location l
                        JOIN State_Summary ss ON l.state_id = ss.state_id
                        JOIN Income i ON l.county_id = i.county_id
                        JOIN Employment e ON l.county_id = e.county_id
                        JOIN Education_Distribution ed ON l.county_id = ed.county_id
                        JOIN Cost_of_Living c ON l.county_id = c.county_id
                        JOIN Cost_Burden_Classification cb ON l.county_id = cb.county_id
                        JOIN Gender g ON l.county_id = g.county_id
                        JOIN Race r ON l.county_id = r.county_id
                        JOIN Occupation o ON l.county_id = o.county_id
                        JOIN Age_Distribution a2 ON l.county_id = a2.county_id
                        JOIN Analysis an ON l.county_id = an.county_id
                        WHERE %s
                        ORDER BY an.Composite_Opportunity_Score DESC
                        LIMIT 200
                        ", $where_clause);
        
        $query = $this->conn->query($sql);
        while ($row = $query->fetch_assoc()) {
            $results[] = $row;
        }

        return $results;
    }
    
    //Compare.php Functions

    public function get_counties($state_id) {
        $counties = [];

        $result = $this->conn->query("SELECT county_id, County_Name FROM Location WHERE state_id=$state_id ORDER BY County_Name");
        while ($row = $result->fetch_assoc()) $counties[] = $row;

        return $counties;
    }

    public function get_states() {
        $states = [];

        $result = $this->conn->query("SELECT state_id, State_Name FROM State_Summary ORDER BY State_Name");
        while ($s = $result->fetch_assoc()) $states[] = $s;

        return $states;
    }

    public function compare($id1, $id2) {
        $counties = [];

        $stmt = $this->conn->prepare("
            SELECT l.county_id, l.County_Name, ss.State_Name,
                i.Median_Household_Income,
                e.Unemployment_Rate_Pct, e.Employment_Rate_Pct,
                ed.Pct_Bachelors, ed.Pct_Masters, ed.Pct_Doctorate, ed.Pct_Any_Degree,
                ee.Less_HS_Earn, ee.Bachelors_Earn, ee.Grad_Earn,
                c.Median_Gross_Rent, c.Median_Home_Value,
                cb.Affordability_Tier,
                g.Total_Pop, g.Pct_Male, g.Pct_Female,
                r.Pct_White, r.Pct_Black, r.Pct_Asian, r.Pct_Hispanic,
                a2.Median_Age,
                eg.HS_to_Bachelors_Gap,
                an.Composite_Opportunity_Score,
                an.Income_Percentile, an.LowRent_Percentile,
                an.EduAttain_Percentile, an.EarningsPremium_Percentile
            FROM location l
            JOIN State_Summary ss ON l.state_id = ss.state_id
            JOIN Income i ON l.county_id = i.county_id
            JOIN Employment e ON l.county_id = e.county_id
            JOIN Education_Distribution ed ON l.county_id = ed.county_id
            JOIN Education_Earnings ee ON l.county_id = ee.county_id
            JOIN Cost_of_Living c ON l.county_id = c.county_id
            JOIN Cost_Burden_Classification cb ON l.county_id = cb.county_id
            JOIN Gender g ON l.county_id = g.county_id
            JOIN Race r ON l.county_id = r.county_id
            JOIN Age_Distribution a2 ON l.county_id = a2.county_id
            JOIN Earnings_Gap eg ON l.county_id = eg.county_id
            JOIN Analysis an ON l.county_id = an.county_id
            WHERE l.county_id IN (?, ?)
        ");

        $stmt->bind_param("ii", $id1, $id2);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $counties[$row['county_id']] = $row;
        $stmt->close();

        return $counties;
    }

    //Demographic Functions

    public function get_demographics($state_id = 0) {
        $results = [];

        $sql = sprintf("
            SELECT l.County_Name, ss.State_Name, l.county_id,
                g.Total_Pop, g.Pct_Male, g.Pct_Female,
                r.Pct_White, r.Pct_Black, r.Pct_Asian, r.Pct_Hispanic,
                a2.Median_Age, i.Median_Household_Income
            FROM Location l
            JOIN State_Summary ss ON l.state_id = ss.state_id
            JOIN Gender g ON l.county_id = g.county_id
            JOIN Race r ON l.county_id = r.county_id
            JOIN Age_Distribution a2 ON l.county_id = a2.county_id
            JOIN Income i ON l.county_id = i.county_id
            %s
            ORDER BY g.Total_Pop DESC
            LIMIT 200
        ", $state_id !== 0 ? "WHERE l.state_id = $state_id" : "");

        $query = $this->conn->query($sql); //Run  query and store results in $results array
        while ($row = $query->fetch_assoc()) { //Fetch each row of the result set as an associative array and append it to the $results array for later
            $results[] = $row;
        }

        return $results;
    }
}

?>
