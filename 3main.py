import mysql.connector
from mysql.connector import Error
import os

__location__ = os.path.realpath(
    os.path.join(os.getcwd(), os.path.dirname(__file__)))

f = open(os.path.join(__location__, 'connections.txt'), "r", encoding="utf-8")

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

count = 1

sql = "INSERT INTO connections (from_url, to_url) VALUES (%s, %s)"
val = []

for l in lines:
    urls = l.split(" ")
    if len(urls) == 2:
        entry = (urls[0], urls[1])
        # Check if the data already exists in the table
        cursor.execute("SELECT COUNT(*) FROM connections WHERE from_url = %s AND to_url = %s", (entry[0], entry[1]))
        count = cursor.fetchone()[0]
        if count == 0:
            val.append(entry)


print("executing...")
cursor.executemany(sql, val)


print("commiting...")

try:
    connection.commit()
except:
    print("Cannot commit!")

cursor.close()
connection.close()