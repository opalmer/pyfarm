:: used for running the main gui on windows
:: the pyw extension prevents print from being seen

@ echo off
copy Main.pyw Main.py
Main.py
del Main.py
