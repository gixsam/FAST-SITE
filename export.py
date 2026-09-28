import sqlite3

def dump_table(cursor, table):
    cursor.execute(f"SELECT * FROM {table}")
    rows = cursor.fetchall()
    if not rows: return ""
    
    col_names = [description[0] for description in cursor.description]
    
    sql = ""
    for row in rows:
        values = []
        for val in row:
            if val is None:
                values.append("NULL")
            elif isinstance(val, (int, float)):
                values.append(str(val))
            else:
                escaped = str(val).replace("'", "''")
                values.append(f"'{escaped}'")
        
        # Using INSERT IGNORE so it won't crash if it exists
        sql += f"INSERT IGNORE INTO {table} ({', '.join(col_names)}) VALUES ({', '.join(values)});\n"
    return sql

conn = sqlite3.connect('fast_site_local.db')
c = conn.cursor()

sql_output = dump_table(c, 'partners')
sql_output += dump_table(c, 'partner_products')
sql_output += dump_table(c, 'partner_product_images')

with open('admin/sync_data.sql', 'w', encoding='utf-8') as f:
    f.write(sql_output)
