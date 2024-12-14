import mysql.connector
from mysql.connector import Error
import os

__location__ = os.path.realpath(os.path.join(os.getcwd(), os.path.dirname(__file__)))

f = open(os.path.join(__location__, 'data.txt'), "r", encoding="utf-8")

lines = f.read().split("\n")

f.close()

def create_connection(host_name, user_name, user_password, data):
    connection = None
    try:
        connection = mysql.connector.connect(
            host="localhost",
            user="root",
            passwd="",
            database="searchdata"
        )
        print("Connection to MySQL DB successful")
    except Error as e:
        print(f"The error '{e}' occurred")

    return connection

connection = create_connection("HOST_NAME", "USER_NAME", "USER_PASSWORD", "DATABASE_NAME")

cursor = connection.cursor()

sql = "INSERT INTO websites (url, title, keywords) VALUES (%s, %s, %s)"
val = []

for l in lines:
    items = l.split("#")
    if len(items) == 3:
        entry = (items[0].replace("#", ""), items[1].replace("#", ""), items[2].replace("#", ""))
        # Check if the data already exists in the table
        cursor.execute("SELECT COUNT(*) FROM websites WHERE url = %s", (entry[0],))
        count = cursor.fetchone()[0]
        if count == 0:
            val.append(entry)

print("executing...")
cursor.executemany(sql, val)

print("committing...")

try:
    connection.commit()
except:
    print("Cannot commit!")


cursor.close()
connection.close()