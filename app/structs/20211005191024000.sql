CREATE TABLE quotes(
   id SERIAL PRIMARY KEY NOT NULL,
   quote TEXT NOT NULL,
   author VARCHAR(60) NOT NULL,
   create_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
