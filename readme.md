# Table of contents
1. [Introduction](#introduction)
2. [Disclaimer](#disclaimer)
3. [Stack](#stack)
3. [Test account](#test-acc)
3. [Log Repository](#log-repo)
3. [Graphics and data](#graphics)
4. [Pipeline](#pipeline)
5. [Important Services](#important-services)
    1. [SaveGame](#is-save-game)
    2. [EventInterpreter](#is-event-interpreter)
    3. [Crawler](#is-crawler)
    3. [TokenFinder & HSapi & ImageApi](#is-download)
6. [Important Routes](#important-routes)
    1. [Routes in AdminController](#/admin)
    1. [/login](#/login)
    2. [/user-games](#/user-games)
    3. [/upload](#/upload)
    3. [//game/{game}/{round}](#/game)



# Introduction 
<div id='introduction'></div>

HSBG is an app that uploads log files from the Blizzard card game Hearthstone, precisely Battlegrounds mode which is Blizzard's interpretation of battle chess genre. After log file is ready on the server, it is parsed into PHP variables, which then are interpreted into a full game that contains every move of the player displayed step by step in twig templates.

You can test the website under this link: [http://hsbg.sasartele.pl/](http://hsbg.sasartele.pl/)

Very simple instruction of basic functionalities is here: [http://hsbg.sasartele.pl/how-it-works](http://hsbg.sasartele.pl/how-it-works)

To jump into testing without registration (very simple one) and uploading files, you can use [test account](#test-acc)

If you want to upload some files, you can find them in [Log Repository](#log-repo)

# Disclaimer 
<div id='disclaimer'></div>

This documentation is mixed with some efforts to explain my approach to some problems that I've encountered. I understand that PHP is not a perfect environment for this type of project, but I was testing what I can do with PHP 7 and Symfony 5 framework, and if I can connect it with one of my hobbies - card games. 

# "Stack"
<div id='stack'></div>

Here is a list of tools that I've used:

- PHP 7.4.13
- Symfony 5.4
- MySQL 5.7
- Bootstrap 4.6 + some custom CSS + TWIG
- Just a touch of custom JS


<div id='test-acc'></div>

# Test Account

To test all functionalities, you can use these credentials: <br><br>
- **Login:** test@test.com<br>
- **Pass:** QWEasd <br><br>

Test account can see the admin UI, but admin functions have been disabled. It contains some preloaded games.


<div id='log-repo'></div>

# Log Repository

If you would like to upload some log files, or just check them out, you can find some of my games inside [this repository](https://github.com/Sapozarom/Log-Files-)


<div id='graphics'></div>

# Resources

Most of the images used to render boards and events are scraped from Hearthstone Client, so they are the property of Activision Blizzard, Inc. 

Three APIs are used to update data about cards, and download their graphics:
- https://develop.battle.net/documentation/hearthstone
- https://hearthstoneapi.com/
- https://hearthstonejson.com/

You can find more info about acquiring data in [Important Services](#important-services) section


<div id='pipeline'></div>

# Pipeline

<em>This is a summary of data processing. Specific services are described in [this chapter](#important-services)</em>

Every Hearthstone game in every mode generates input into Power.log file. This file is deleted every time a client app is closed. For the purpose of this project I've created a second repository containing only log files that can be used to test functionality of $this app. 

To upload a file, you use a simple form on the website. After you click "Save" button, the processing scripts are triggered, requests are put in queue and maintained async in background. Data about every file is saved in DB.

All games are put into one file by game client, so it can grow quite big. After file is uploaded, it is split into single games. 

Next step is making it understandable to PHP. If you look at the log file, every verse started with timestamp is a unique data point. Here is where PHP shines, because it has a great tool to read files lane by lane. 

<details open>
<summary>.log sample</summary> 

    10:02:28.0712982 PowerTaskList.DebugPrintPower() - BLOCK_START BlockType=TRIGGER Entity=[entityName=BaconShop8PlayerEnchant id=47 zone=PLAY zonePos=0 cardId=TB_BaconShop_8P_PlayerE player=2] EffectCardId=System.Collections.Generic.List`1[System.String] EffectIndex=11 Target=0 SubOption=-1 TriggerKeyword=TAG_NOT_SET
    10:02:28.0712982 PowerTaskList.DebugPrintPower() -     TAG_CHANGE Entity=GameEntity tag=2022 value=1 
    10:02:28.0712982 PowerTaskList.DebugPrintPower() -     TAG_CHANGE Entity=GameEntity tag=1453 value=1 
    10:02:28.0712982 PowerTaskList.DebugPrintPower() -     FULL_ENTITY - Updating [entityName=Refreshing Anomaly id=375 zone=PLAY zonePos=1 cardId=BGS_116 player=10] CardID=BGS_116
    10:02:28.0712982 PowerTaskList.DebugPrintPower() -         tag=CONTROLLER value=10
    10:02:28.0712982 PowerTaskList.DebugPrintPower() -         tag=CARDTYPE value=MINION
    10:02:28.0712982 PowerTaskList.DebugPrintPower() -         tag=1196 value=1

</details> 


So lane by lane and timestamp by timestamp, data from the file is analyzed and converted from String into PHP variables. 

Some data are just one-liners like this one ```TAG_CHANGE Entity=GameEntity tag=2022 value=1```, which will change one property ("tag") of entity. But there are also operations like `FULL_ENTITY - Updating` that changes many properties and the data are concluded in next few rows. This rows will be put in block and interpreted as one event.

In next step all these blocks are transformed into PHP objects and next into game events. These are stored into a database.

Last step is just displaying formatted data from the DB and voilà we have a readable game history.

So you can summarize pipeline by using this graph

[Upload .log file] --> [Send DivideFileMessage] --> [run LogFileDivider service] --> [send ParseGameMessage] --> [run SaveGame service] --> [Save game into database]

Game in database is represented by this data structure:

                                         
[Game] -> [Rounds] -> [Events] -> [Boards]&[Data] -> [Cards]


<div id='important-services'></div>

# Important services

<div id='is-save-game'></div>

## SaveGame

This service is first one that is started after dividing .log file. It handles all data processing from string to Objects next to database. It triggers other big service **EventInterpreter** to process data from file. Next this data are saved as native app **Entities** and stored in database. 


<div id='is-event-interpreter'></div>

## EventInterpreter

This service do most of the heavy work referred to processing data scraped from .log file. It works in tandem with **GameHistoryService** that store all events created during the game. 

EventInterpreters role is to create Objects from data represented in string. It also tracks the change of property values in time. Everytime **GameHistoryService** creates new event, the image of current values of players resources is created.

To get the data EventInterpreter triggers **Crawler**, last big service during the process.

<div id='is-crawler'></div>

## Crawler

This one is far from perfect. It's one of the first services that was created, used for scraping useful data from .log strings.
It is mostly playing with Strings and grouping data points if it is required.  Crawler works in tandem with **EventCollector** which is collecting all important data changes.

<div id='is-download'></div>

## TokenFinder & HSapi & ImageApi

Three services that are used to get core data about cards and heroes. They connect with three different APIs:
- TokenFinder - https://develop.battle.net/documentation/hearthstone
- HSapi - https://hearthstoneapi.com/
- ImageApi - https://hearthstonejson.com/



<div id='important-routes'></div>

# Important routes

<div id='/admin'></div>

## Routes in AdminController

These were made to download core data to DB and store them in JSON files. Files are stored in folders named after builds ID of HS game client. Right now two builds are supported: 88998 and 141643


<div id='/login'></div>

## /login /register

Simple authentication system based on `symfony/security` bundle.

<div id='/user-games'></div>

## /user-games

Displays list of uploaded games

<div id='/upload'></div>

## /upload

Basic form that allows to upload .log file. The interesting part of this project starts after form is submitted. Whole data processing takes some time, so with usage of `symfon/messenger, next steps are launched in background.


<div id='/game'></div>

## /game/{game}/{round}

Takes data from DB and renders game view round by round











