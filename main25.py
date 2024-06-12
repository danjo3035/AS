import requests
import mysql.connector

# API connection.
API_URL = 'https://mspclouds.com/api/v1/cloudbackup/reports/summary?api_key=BD9B-1134-FF3E-FD3D'
HEADERS = {'accept': 'application/json'}

# Database connection parameters.
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'backup_extel',
    'password': 'backup_extel',
    'database': 'backup_extel'
}

def create_database(cursor):
    try:
        cursor.execute("CREATE DATABASE IF NOT EXISTS backup_extel")
        print("Database 'backup_extel' created or already exists.")
    except mysql.connector.Error as err:
        print(f"Error creating database: {err}")
        raise

def create_tables(cursor):
    # Create 'company' table.
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS company (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT,
            company_type VARCHAR(255),
            company_name VARCHAR(255) NOT NULL,
            company_logo VARCHAR(255)
        )
    """)
    
    # Create 'status' table with a foreign key reference to 'company'.
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS company_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fk_company INT,
            info INT,
            success INT,
            warning INT,
            error INT,
            total INT,
            FOREIGN KEY (fk_company) REFERENCES company(id)
        )
    """)

    # Create 'clients' table with a foreign key reference to 'company'.
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS clients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT,
            fk_company INT,
            client_type VARCHAR(50),
            client_name VARCHAR(255),
            client_logo VARCHAR(255),
            FOREIGN KEY (fk_company) REFERENCES company(id)
        )
    """)
    
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS client_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fk_client INT,
            info INT,
            success INT,
            warning INT,
            error INT,
            total INT,
            FOREIGN KEY (fk_client) REFERENCES clients(id)
        )
    """)

    # Create 'backupsets' table with a foreign key reference to 'clients'.
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS backupsets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fk_client INT,
            login_name VARCHAR(255),
            login_description VARCHAR(255),
            backup_set_id VARCHAR(255),
            backup_set_name VARCHAR(255),
            destination_name VARCHAR(255),
            last_backup_job_date DATETIME,
            last_backup_job_status_code VARCHAR(50),
            last_backup_job_status_description VARCHAR(255),
            last_success_backup_job_date DATETIME,
            FOREIGN KEY (fk_client) REFERENCES clients(id)
        )
    """)

    print("Tables created or already exist.")

def tables_exist(cursor):
    cursor.execute("SHOW TABLES LIKE 'company'")
    company_table_exists = cursor.fetchone() is not None
    
    cursor.execute("SHOW TABLES LIKE 'company_status'")
    status_table_exists = cursor.fetchone() is not None

    cursor.execute("SHOW TABLES LIKE 'clients'")
    clients_table_exists = cursor.fetchone() is not None
    
    cursor.execute("SHOW TABLES LIKE 'client_status'")
    status_table_exists = cursor.fetchone() is not None

    cursor.execute("SHOW TABLES LIKE 'backupsets'")
    backupsets_table_exists = cursor.fetchone() is not None

    return company_table_exists and status_table_exists and clients_table_exists and backupsets_table_exists

def insert_or_update_data(cursor, company_data, status_data, clients_data):
    # Check if the company already exists.
    cursor.execute("SELECT id FROM company WHERE company_id = %s", (company_data['id'],))
    existing_company = cursor.fetchone()

    if existing_company:
        company_id = existing_company[0]
        
        # Update data in 'company' table.
        cursor.execute("""
            UPDATE company
            SET company_name = %s, company_type = %s, company_logo = %s
            WHERE id = %s
        """, (company_data['name'],company_data['type'], company_data['logo'], company_id))
        
        # Update data in 'company_status' table.
        cursor.execute("""
            UPDATE company_status
            SET info = %s, success = %s, warning = %s, error = %s, total = %s
            WHERE fk_company = %s
        """, (status_data['info'], status_data['success'], status_data['warning'], status_data['error'], status_data['total'], company_id))
        
    else:
        # Insert data into 'company' table.
        cursor.execute("""
            INSERT INTO company (company_id, company_type, company_name, company_logo)
             VALUES (%s, %s, %s, %s)
        """, (company_data['id'], company_data['type'], company_data['name'], company_data['logo']))
        company_id = cursor.lastrowid
        
        # Insert data into 'company_status' table.
        cursor.execute("""
            INSERT INTO company_status (fk_company, info, success, warning, error, total)
            VALUES (%s, %s, %s, %s, %s, %s)
        """, (company_id, status_data['info'], status_data['success'], status_data['warning'], status_data['error'], status_data['total']))

    for client_data in clients_data:
        # Check if the client already exists.
        cursor.execute("SELECT id FROM clients WHERE client_id = %s", (client_data['id'],))
        existing_client = cursor.fetchone()

        if existing_client:
            client_id = existing_client[0]
            client_status = client_data['status']
            
            # Update data in 'clients' table.
            cursor.execute("""
                UPDATE clients
                SET fk_company = %s, client_type = %s, client_name = %s, client_logo = %s
                WHERE id = %s
            """, (company_id, client_data['type'], client_data['name'], client_data['logo'], client_id))
            
            # Update data in 'client_status' table.
            cursor.execute("""
                UPDATE client_status
                SET info = %s, success = %s, warning = %s, error = %s, total = %s
                WHERE fk_client = %s
            """, (client_status['info'], client_status['success'], client_status['warning'], client_status['error'], client_status['total'], client_id))
            
        else:
            # Insert data into 'clients' table.
            cursor.execute("""
                INSERT INTO clients (client_id, fk_company, client_type, client_name, client_logo)
                VALUES (%s, %s, %s, %s, %s)
            """, (client_data['id'], company_id, client_data['type'], client_data['name'], client_data['logo']))
            client_id = cursor.lastrowid
            
            # Insert data into 'client_status' table.
            cursor.execute("""
                INSERT INTO client_status (fk_client, info, success, warning, error, total)
                VALUES (%s, %s, %s, %s, %s, %s)
            """, (client_id, client_status['info'], client_status['success'], client_status['warning'], client_status['error'], client_status['total']))

        # Insert or update data in 'backupsets' table.
        backupset_data = client_data['backupsets']
        for backupset in backupset_data:
            cursor.execute("SELECT id FROM backupsets WHERE fk_client = %s AND backup_set_id = %s", (client_id, backupset['backup_set_id']))
            existing_backupset = cursor.fetchone()

            if existing_backupset:
                # Update data in 'backupsets' table.
                cursor.execute("""
                    UPDATE backupsets
                    SET login_name = %s, login_description = %s, backup_set_name = %s, destination_name = %s,
                        last_backup_job_date = %s, last_backup_job_status_code = %s,
                        last_backup_job_status_description = %s, last_success_backup_job_date = %s
                    WHERE id = %s
                """, (backupset['login_name'], backupset['login_description'], backupset['backup_set_name'], backupset['destination_name'],
                      backupset['last_backup_job_date'], backupset['last_backup_job_status_code'],
                      backupset['last_backup_job_status_description'], backupset['last_success_backup_job_date'], existing_backupset[0]))
            else:
                # Insert data into 'backupsets' table.
                cursor.execute("""
                    INSERT INTO backupsets (fk_client, login_name, login_description, backup_set_id, backup_set_name, destination_name,
                                            last_backup_job_date, last_backup_job_status_code,
                                            last_backup_job_status_description, last_success_backup_job_date)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                """, (client_id, backupset['login_name'], backupset['login_description'], backupset['backup_set_id'], backupset['backup_set_name'],
                      backupset['destination_name'], backupset['last_backup_job_date'], backupset['last_backup_job_status_code'],
                      backupset['last_backup_job_status_description'], backupset['last_success_backup_job_date']))

    print("Data inserted or updated successfully.")
    
def main():
    # Database connection without specifying the database initially.
    db_connection = mysql.connector.connect(
        host=DB_CONFIG['host'],
        user=DB_CONFIG['user'],
        password=DB_CONFIG['password']
    )
    db_cursor = db_connection.cursor()

    try:
        create_database(db_cursor)

        # Explicitly close the connection before reconnecting with the selected database.
        db_cursor.close()
        db_connection.close()

        # Reconnect with the selected database.
        db_connection = mysql.connector.connect(**DB_CONFIG)
        db_cursor = db_connection.cursor()

        # Select the 'extel' database.
        db_cursor.execute("USE backup_extel")

        create_tables(db_cursor)

        # Fetch data from API.
        response = requests.get(url=API_URL, headers=HEADERS)
        data_from_api = response.json()

        company_data = data_from_api.get('company')
        status_data = data_from_api.get('status')
        clients_data = data_from_api.get('clients')

        if clients_data:
            insert_or_update_data(db_cursor, company_data, status_data, clients_data)

        # Commit changes.
        db_connection.commit()
        print("Data committed successfully.")

    finally:
        db_cursor.close()
        db_connection.close()
        print("Database connection closed.")

if __name__ == "__main__":
    main()
