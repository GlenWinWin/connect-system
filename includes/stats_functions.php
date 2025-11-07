<?php
/**
 * Statistics functions for Dashboard
 */

/**
 * Get statistics grouped by I AM (First Timer/Visitor)
 */
function getStatsByVisitorType($pdo) {
    $sql = "
        SELECT 
            iam,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM first_timers)), 2) as percentage
        FROM first_timers 
        GROUP BY iam 
        ORDER BY count DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get statistics grouped by Life Stages (Age Groups)
 */
function getStatsByLifeStages($pdo) {
    $sql = "
        SELECT 
            age_group,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM first_timers)), 2) as percentage
        FROM first_timers 
        GROUP BY age_group 
        ORDER BY 
            CASE 
                WHEN age_group = 'River Youth (13 to 19 years old)' THEN 1
                WHEN age_group = 'Young Adult (20 to 35 years old)' THEN 2
                WHEN age_group = 'River Men (36 to 50 years old)' THEN 3
                WHEN age_group = 'River Women (36 to 50 years old)' THEN 4
                WHEN age_group = 'Seasoned (51 years old and above)' THEN 5
                ELSE 6
            END
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get monthly statistics for charts
 */
function getMonthlyStats($pdo) {
    $sql = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            iam,
            COUNT(*) as count
        FROM first_timers 
        GROUP BY DATE_FORMAT(created_at, '%Y-%m'), iam
        ORDER BY month
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get service attendance statistics
 */
function getServiceStats($pdo) {
    $sql = "
        SELECT 
            service_attended,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM first_timers)), 2) as percentage
        FROM first_timers 
        GROUP BY service_attended 
        ORDER BY count DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get lifegroup interest statistics
 */
function getLifegroupStats($pdo) {
    $sql = "
        SELECT 
            lifegroup,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM first_timers)), 2) as percentage
        FROM first_timers 
        GROUP BY lifegroup 
        ORDER BY count DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>