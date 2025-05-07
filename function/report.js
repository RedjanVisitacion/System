const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

// Database connection configuration
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'elecom'
};

/**
 * Generate a comprehensive election report
 * @param {string} format - The format of the report ('json' or 'txt')
 * @returns {Promise<string>} - The path to the generated report file
 */
async function generateElectionReport(format = 'json') {
    try {
        // Create database connection
        const connection = await mysql.createConnection(dbConfig);

        // Get election dates
        const [electionDates] = await connection.execute(
            'SELECT * FROM election_dates ORDER BY id DESC LIMIT 1'
        );

        // Get all candidates with their results
        const [candidates] = await connection.execute(`
            SELECT 
                c.candidate_id,
                c.name,
                c.department,
                c.position,
                c.age,
                c.platform,
                COALESCE(r.votes, 0) as votes
            FROM candidate c
            LEFT JOIN result r ON c.candidate_id = r.candidate_id
            ORDER BY c.department, c.position, r.votes DESC
        `);

        // Get total votes per department
        const [departmentStats] = await connection.execute(`
            SELECT 
                department,
                COUNT(DISTINCT user_id) as total_voters
            FROM user
            WHERE role = 'student'
            GROUP BY department
        `);

        // Compile report data
        const reportData = {
            electionPeriod: {
                startDate: electionDates[0]?.start_date,
                endDate: electionDates[0]?.end_date,
                resultsDate: electionDates[0]?.results_date
            },
            departmentStats: departmentStats,
            candidates: candidates,
            generatedAt: new Date().toISOString()
        };

        // Create reports directory if it doesn't exist
        const reportsDir = path.join(__dirname, '../reports');
        if (!fs.existsSync(reportsDir)) {
            fs.mkdirSync(reportsDir);
        }

        // Generate filename with timestamp
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const filename = `election_report_${timestamp}.${format}`;
        const filepath = path.join(reportsDir, filename);

        // Write report to file
        if (format === 'json') {
            fs.writeFileSync(filepath, JSON.stringify(reportData, null, 2));
        } else if (format === 'txt') {
            const textReport = generateTextReport(reportData);
            fs.writeFileSync(filepath, textReport);
        }

        await connection.end();
        return filepath;
    } catch (error) {
        console.error('Error generating report:', error);
        throw error;
    }
}

/**
 * Generate a formatted text report
 * @param {Object} data - The report data
 * @returns {string} - The formatted text report
 */
function generateTextReport(data) {
    let report = 'ELECTION REPORT\n';
    report += '==============\n\n';

    // Election Period
    report += 'Election Period:\n';
    report += `Start Date: ${new Date(data.electionPeriod.startDate).toLocaleString()}\n`;
    report += `End Date: ${new Date(data.electionPeriod.endDate).toLocaleString()}\n`;
    report += `Results Date: ${new Date(data.electionPeriod.resultsDate).toLocaleString()}\n\n`;

    // Department Statistics
    report += 'Department Statistics:\n';
    report += '====================\n';
    data.departmentStats.forEach(dept => {
        report += `${dept.department}: ${dept.total_voters} registered voters\n`;
    });
    report += '\n';

    // Candidate Results
    report += 'Candidate Results:\n';
    report += '=================\n';
    let currentDepartment = '';
    let currentPosition = '';

    data.candidates.forEach(candidate => {
        if (candidate.department !== currentDepartment) {
            currentDepartment = candidate.department;
            report += `\n${currentDepartment}\n`;
            report += '-'.repeat(currentDepartment.length) + '\n';
            currentPosition = '';
        }

        if (candidate.position !== currentPosition) {
            currentPosition = candidate.position;
            report += `\n${currentPosition}:\n`;
        }

        report += `${candidate.name} - ${candidate.votes} votes\n`;
    });

    report += `\nReport generated at: ${new Date(data.generatedAt).toLocaleString()}\n`;
    return report;
}

module.exports = {
    generateElectionReport
}; 