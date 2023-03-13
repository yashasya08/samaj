<?php
class crud
{

    private $db_hostname = "localhost";
    private $db_username = "root";
    private $db_password = "";
    private $db_name = "crud";

    private $mysqli = "";
    private $conn = false;
    private $result = array();


    public function __construct()
    {
        if (!$this->conn) {
            $this->mysqli = new mysqli($this->db_hostname, $this->db_username, $this->db_password, $this->db_name);
            $this->conn = true;
            if ($this->mysqli->connect_error) {
                return false;
            }
        } else {
            return true;
        }
    }

    public function insert($table, $params = array())
    {
        if ($this->tableExist($table)) {
            $table_col = implode(',', array_keys($params));
            $table_val = implode("','", $params);
            $sql = "insert into $table($table_col) values('$table_val')";
            if ($this->mysqli->query($sql)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function update($table, $params = array(), $where = null)
    {
        if ($this->tableExist($table)) {
            $args = array();
            foreach ($params as $key => $value) {
                $args[] = "$key='$value'";
            }
            $sql = "update $table set " . implode(', ', $args);
            if ($where != null) {
                $sql .= " where $where";
            }
            if ($this->mysqli->query($sql)) {
                if ($this->mysqli->affected_rows) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function delete($table, $where = null)
    {
        if ($this->tableExist($table)) {
            $sql = "delete from $table";
            if ($where != null) {
                $sql .= " where $where";
            }
            if ($this->mysqli->query($sql)) {
                if ($this->mysqli->affected_rows) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function select($table, $rows = "*", $join = null, $where = null, $order = null, $limit = null)
    {
        if ($this->tableExist($table)) {
            $sql = "select $rows from $table ";
            if ($join != null) {
                $sql .= " join $join ";
            }
            if ($where != null) {
                $sql .= " where $where ";
            }
            if ($order != null) {
                $sql .= " order by $order ";
            }
            if ($limit != null) {
                $sql .= " limit 0,$limit ";
            }
            // echo "$sql";
            $query = $this->mysqli->query($sql);
            if ($query) {

                // $this->result = $query->fetch_all(MYSQLI_ASSOC);
                return $query;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // for using array series with index
    // public function sql($sql)
    // {
    //     $query = $this->mysqli->query($sql);
    //     if ($query) {
    //         $this->result = $query->fetch_all(MYSQLI_ASSOC);
    //         return true;
    //     } else {
    //         return false;
    //     }
    // }

    //for using loop
    public function sql($sql)
    {
        $query = $this->mysqli->query($sql);
        return $query;
    }

    public function __destruct()
    {
        if ($this->conn) {
            if ($this->mysqli->close()) {
                $this->conn = false;
                return true;
            }
        } else {
            return false;
        }
    }

    private function tableExist($table)
    {
        $sql = "show tables from $this->db_name like '$table'";

        $tableInDb = $this->mysqli->query($sql);
        if ($tableInDb) {
            if ($tableInDb->num_rows == 1) {
                return true;
            } else {
                return false;
            }
        }
    }
    public function getResult()
    {
        $val = $this->result;
        $this->result = array();
        return $val;
    }

    // 
    // 
    // used functions below
    // 
    // 



    public function Elligible($user_id)
    {
        $res = $this->sql("select dob from dobtable where id=$user_id and mstatue='unmarried'");
        if ($res != "") {
            while ($row = $res->fetch_assoc()) {
                $today = new DateTime();
                $birthdate = new DateTime($row['dob']);
                $age = $today->diff($birthdate)->y;
                if ($age >= 18) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }



    public function UserRegister($values = array())
    {
        return $this->insert('insertt', $values);
    }



    public function MarkExpired($user_id)
    {
        return $this->update('users', ['status' => 'expired'], "id=$user_id");
    }






    // 
    // 
    // admin functions below
    // 
    // 


    public function AdminActivity($admin_id, $operation)
    {

        $this->insert('adminactivity', ['admin_id' => "$admin_id", 'operation' => "$operation"]);
    }



    public function AdminLogin($username, $password)
    {
        $res = $this->sql("select * from login_super_admin where username='$username' and password='$password'");
        if ($res != "") {
            while ($row = $res->fetch_assoc()) {
                if (($row['username'] == $username) and ($row['password'] == $password)) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }



    public function ChangeAdminPassword($username, $old, $new)
    {
        $res = $this->sql("select * from login_admin where username='$username'");
        if ($res != "") {
            while ($row = $res->fetch_assoc()) {
                if (($row['password'] == $old)) {
                    if ($this->update('login_admin', ['password' => "$new"])) {
                        return "Success";
                    } else {
                        return "Error";
                    }
                } else {
                    return "Password Do not Match";
                }
            }
        } else {
            return "Error";
        }
    }



    public function AcceptHead($admin_id, $family_id, $password, $values = array(), $where)
    {
        $this->AdminActivity($admin_id, "Accepted Head, Family ID - $family_id");
        return ($this->insert('user_login', ['family_id' => "$family_id", 'password' => "$password", 'status' => "active"]) and $this->update('insertt', $values, $where));
    }

    public function ChangeHead($family_id, $new_head_id)
    {
        //new_head_id is member which need to become head
        $result = $this->sql("select * from users where id=$new_head_id");
        if ($result != "") {
            while ($row = $result->fetch_assoc()) {
                return $this->update('head', ['status' => 'deactive'], "family_id=$family_id");
            }
        } else {
            return false;
        }
    }

    public function AcceptMember($admin_id, $family_id, )
    {
        // $this->AdminActivity($admin_id, "Accepted Member, Family ID - $family_id");
        // return ($this->insert('user_login', ['family_id' => "$family_id", 'password' => "$password", 'status' => "active"]) and $this->update('insertt', $values, $where));
    }

    public function AddNotice($admin_id, $title, $subtitle, $description, $district)
    {
        $this->AdminActivity($admin_id, "Added Notice - $title");
        return $this->insert('notice', ['title' => "$title", 'sub_title' => "$subtitle", 'description' => "$description", 'district' => "$district"]);
    }

    public function GalleryInsert($admin_id, $event_name, $category, $filename, $district)
    {
        $this->AdminActivity($admin_id, "Added Event - $event_name");
        return $this->insert('gallery', ['event_name' => "$event_name", 'category' => "$category", 'district' => "$district", 'filename' => "$filename"]);
    }


    public function AddHall($admin_id, $hall_name, $hall_price, $hall_location, $district)
    {
        $this->AdminActivity($admin_id, "Added Hall - $hall_price");
        return $this->insert('samaj_halls', ['hall_name' => "$hall_name", 'hall_price' => "$hall_price", 'hall_location' => "$hall_location", 'district' => "$district"]);
    }

    public function ApproveHall($admin_id, $hall_id, $family_id, $date)
    {
        $this->AdminActivity($admin_id, "Approved Hall - $hall_id, Rent To Family id - $family_id");
        return $this->insert('hall_bookings', ['hall_id' => "$hall_id", 'rent_to' => "$family_id", 'date' => "$date"]);
    }

    public function AddMemberPanel($user_id, $position, $district)
    {
        return $this->insert('member_panel', ['user_id' => "$user_id", 'position' => "$position", 'district' => "$district", 'status' => "active"]);
    }
    public function UpdateMemberPanel($user_id, $position, $district)
    {
        return $this->update('member_panel', ['user_id' => "$user_id", 'position' => "$position", 'district' => "$district", 'status' => "active"]);
    }
    public function DeleteMemberPanel($user_id)
    {
        return $this->update('member_panel', ['status' => "active"]);
    }




    // 
    // 
    // super admin functions below
    // 
    // 



    public function SuperAdminActivity($operation)
    {
        $this->insert('activity_super_admin', ['operation' => "$operation"]);
    }

    public function SuperAdminLogin($username, $password)
    {
        $res = $this->sql("select * from login_super_admin where username='$username' and password='$password'");
        if ($res != "") {
            while ($row = $res->fetch_assoc()) {

                if (($row['username'] == $username) and ($row['password'] == $password)) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }



    public function AddAdmin($user_id, $username, $password, $district)
    {
        $this->SuperAdminActivity("Added Admin, username = $username , for district = $district");
        return $this->insert('login_admin', ['user_id' => "$user_id", 'username' => "$username", 'password' => "$password", 'district' => "$district"]);
    }

    public function RemoveAdmin($user_id)
    {
        $this->SuperAdminActivity("Removed Admin, user id = $user_id ");
        return $this->update('login_admin', ['status' => "deactive"], "user_id=$user_id");
    }

    // public function DeleteUser($user_id)
    // {
    //     $this->SuperAdminActivity("Deleted User, user id = $user_id ");
    //     return $this->update('login_admin', ['status' => "deactive"], "user_id=$user_id");
    // }

    public function ChangeSuperPassword($old, $new)
    {
        $res = $this->sql("select * from login_super_admin");
        if ($res != "") {
            while ($row = $res->fetch_assoc()) {

                if (($row['password'] == $old)) {
                    if ($this->update('login_super_admin', ['password' => "$new"])) {
                        return "Success";
                    } else {
                        return "Error";
                    }
                } else {
                    return "Password Do not Match";
                }
            }
        } else {
            return "Error";
        }
    }



// 
// 
// general functions (main site)
// 
// 


// public function ShowAd()
// {
//     $res = $this->sql("select * from advertisement");
//     if ($res != "") {
//         while ($row = $res->fetch_assoc()) {

//             if (($row['password'] == $old)) {
//                 if ($this->update('login_super_admin', ['password' => "$new"])) {
//                     return "Success";
//                 } else {
//                     return "Error";
//                 }
//             } else {
//                 return "Password Do not Match";
//             }
//         }
//     } else {
//         return "Error";
//     }
// }
}