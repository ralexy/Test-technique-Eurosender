# Technical Test: Link Shortener

This project is the result of a technical test aiming to produce an API that can shorten links while respecting the following constraints:

- Efficiency, by being fast and storing the shortened links on the fewest possible characters,

- Resilience, by being able to support 1000 link insertions simultaneously,

- Permissiveness, by allowing to save at least 1 billion links,

- Scalability, by offering the possibility to distribute the database on a distributed network.

The project relies on the following technologies:

- NginX,

- PHP 8.1 FPM,

- MongoDB 6,

- MongoDB Express.

Documentation is available in the ./documentation folder.

## Functioning

The choice was made to use lowercase and uppercase letters from a to z, plus the numbers from 0 to 9 to generate a hash. Thus, we obtain 62 possible values per character.

| Number of characters | Number of storable hashes |
| -------------------- | ------------------------- |
| 1                    | 62                        |
| ...                  | ...                       |
| 5                    | 916 M                     |
| 6                    | 56 B                      |

With this technique, we can store 916 million links on 5 characters and 56 billion links on only 6 characters.

As the generation progresses, we find ourselves in the case where, for example, 61 out of 62 characters have been generated, which creates a high risk of collision when generating a hash. A simple and effective solution was to give a number of 3 attempts to generate a hash on n characters.

- If these 3 attempts fail, the hash increases by 1 character.

This solution speeds up the generation of links and only increases the hash by 1 character to store 1 billion links, which is an excellent compromise.

## Getting Started

These instructions will get you a copy of the project up and running on your local development machine for testing purposes.

### Prerequisites

Docker must be installed on your machine, more information here: [https://www.docker.com/](https://www.docker.com/)

### Installation

You must be in the root folder of the project and enter the following two commands:

```shell
docker-compose build
docker-compose up
```

## API Functioning

The two usable method requests are **addLink** and **getHash**, here are two examples of requests that can be passed to the API:
The API can be accessed here: [http://localhost/](http://localhost/) **Action to shorten a URL:**

- GET | POST [http://localhost/?action=addUrl&link={yourUrl}](http://localhost/?action=addUrl&link=%7ByourUrl%7D) **Action to retrieve a URL via its Hash:**

- GET | POST [http://localhost/?action=getHash&hash={yourHash}](http://localhost/?action=getHash&hash=%7ByourHash%7D)

---

## Testing the application

### Consultation of the database

You can consult the database at this address: [https://localhost:8081](https://localhost:8081/)

```shell
User: mexpress
Password: mexpress
```

### Launching unit tests

1. Retrieval of the docker container id for PHP

```shell
docker ps
```

![](C:\Users\AlexyRousseau\AppData\Roaming\marktext\images\2023-04-03-20-44-26-image.png)

2. Launching unit tests

```shell
docker exec -it {yourContainerID}./vendor/bin/phpunit
./tests/ApiMethodsTest.php
```

## Versioning

GitHub was used

## Author(s)

**Alexy ROUSSEAU** - [contact@alexy-rousseau.com](mailto:contact@alexy-rousseau.com)
