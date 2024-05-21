#Antonio Zangla e Elia Zanotti 5G 04/03/2024
import time
import datetime
import json
import socket

import base64
import threading
from picamera import PiCamera

import pigpio
import Adafruit_DHT as dht
import Adafruit_BMP.BMP085 as BMP085

COD_TERMOMETRO = 1
COD_IGROMETRO = 2
COD_PLUVIOMETRO = 3
COD_BAROMETRO = 4
COD_BANDERUOLA = 5
COD_ANEMOMETRO = 6
COD_CAMERA = 7

#MAPPATURA GPIO
DIR0_PIN = 6
DIR1_PIN = 13
DIR2_PIN = 19
DIR3_PIN = 26
ANEMO_PIN = 5
PLUV_PIN = 20
DHT11_PIN = 22
BMP_SCL_PIN = 3
BMP_SDA_PIN = 2

pi = pigpio.pi()
if not pi.connected:
    print("Errore pigpiod")
    exit()
    
    
class VentoAutocostruito:
    __ANEMOMETRO = 0.6
    __NUM_RILEVAMENTI = 150
    __TEMPO_LETTURA_ANEMOMETRO = 2
    
    
    __lock=threading.Lock()
    __listaDir= []
    __listaAne = []
    
    def __init__ (self, codBanderuola, codAnemometro, anemo_pin, dir0_pin, dir1_pin, dir2_pin, dir3_pin):
        self.codBanderuola = codBanderuola
        self.codAnemometro = codAnemometro
        self.dir0_pin = dir0_pin
        self.dir1_pin = dir1_pin
        self.dir2_pin = dir2_pin
        self.dir3_pin = dir3_pin
        pi.set_mode(dir0_pin, pigpio.INPUT)
        pi.set_pull_up_down(DIR0_PIN, pigpio.PUD_UP)
        pi.set_mode(dir1_pin, pigpio.INPUT)
        pi.set_pull_up_down(DIR1_PIN, pigpio.PUD_UP)
        pi.set_mode(dir2_pin, pigpio.INPUT)
        pi.set_pull_up_down(DIR2_PIN, pigpio.PUD_UP)
        pi.set_mode(dir3_pin, pigpio.INPUT)
        pi.set_pull_up_down(DIR3_PIN, pigpio.PUD_UP)
        self.callbackAnemo = pi.callback(anemo_pin, pigpio.FALLING_EDGE)
        pi.set_pull_up_down(anemo_pin,pigpio.PUD_OFF)
        self.callbackAnemo.reset_tally()
        self.__leggi()
        
    def __leggi(self):
        timer=threading.Timer(self.__TEMPO_LETTURA_ANEMOMETRO,self.__leggi).start()
        anem = self.callbackAnemo.tally()
        self.callbackAnemo.reset_tally()
        dire = self.__leggiDirezioneGray()
        self.__lock.acquire()
        if len(self.__listaDir) >= self.__NUM_RILEVAMENTI:
            self.__listaDir.pop(0)
            self.__listaAne.pop(0)
            
        self.__listaDir.append(dire)
        self.__listaAne.append(anem)
        self.__lock.release()
        
    def media(self):
        self.__lock.acquire()
        media = sum(self.__listaAne)/len(self.__listaAne)
        self.__lock.release()
        m = (media/self.__TEMPO_LETTURA_ANEMOMETRO)*self.__ANEMOMETRO
        return(round(m,1))
    
    def minima(self):
        self.__lock.acquire()
        m = (min(self.__listaAne)/self.__TEMPO_LETTURA_ANEMOMETRO)*self.__ANEMOMETRO
        self.__lock.release()
        return(round(m,1))
    
    def massima(self):
        self.__lock.acquire()
        m = (max(self.__listaAne)/self.__TEMPO_LETTURA_ANEMOMETRO)*self.__ANEMOMETRO
        self.__lock.release()
        return (round(m,1))
    
    def direzione(self):
        freqDirPrev = 0
        dirPrev = ""
        self.__lock.acquire()
        for dir in self.__listaDir:
            freq = self.__listaDir.count(dir)
            if(freqDirPrev < freq):
                dirPrev = dir
        self.__lock.release()
        return(dirPrev)
    
    def __leggiDirezioneGray(self):
        dire = 0b0
        
        try:
            dire = dire + int(pi.read(self.dir0_pin) <<0)
            dire = dire + int(pi.read(self.dir1_pin) <<1)
            dire = dire + int(pi.read(self.dir2_pin) <<2)
            dire = dire + int(pi.read(self.dir3_pin) <<3)
            
        except:
            print("ERRORE LETTURA DIREZIONE DEL VENTO")
            return None
        
        if   dire == 0b0000:
             return "N"
        elif dire == 0b0010:
            return "NNE"
        elif dire == 0b0011:
            return "NE"
        elif dire == 0b1011:
            return "ENE"
        elif dire == 0b1010:
            return "E"
        elif dire == 0b1000:
            return "ESE"
        elif dire == 0b1001:
            return "SE"
        elif dire == 0b1101:
            return "SSE"
        elif dire == 0b1100:
            return "S"
        elif dire == 0b1110:
            return "SSW"
        elif dire == 0b1111:
            return "SW"
        elif dire == 0b0111:
            return "WSW"
        elif dire == 0b0110:
            return "W"
        elif dire == 0b0100:
            return "WNW"
        elif dire == 0b0101:
            return "NW"
        elif dire == 0b0001:
            return "NNW"
        
    def __del__(self):
        pass
        #self.timer.cancel()
        #self.callbackAnemo.cancel()


class BarometroBMP180:
    def __init__(self,codBarometro):
        try:
            self.codBarometro = codBarometro
            self.__sensor = BMP085.BMP085()
        except:
            print("ERRORE LETTURA PRESSIONE BAROMETRICA!")
            return None
        
    def LeggiPressioneBarometrica(self):
        try:
            p= self._sensor.read_pressure()
        except:
            print("ERRORE LETTURA PRESSIONE BAROMETRICA!")
            return None
        #return round(p/100)
        return 1
        
class Dht11:
    
    def __init__(self, codTermometro,codIgrometro, dht11_pin):
        self.codTermometro = codTermometro
        self.codIgrometro = codIgrometro
        self.__dht11_pin = dht11_pin
        
    def LeggiTemperaturaUmidita(self):
        try:
            h,t = dht.read_retry(dht.DHT11, self.__dht11_pin)
            t = round(t,1)
            h = round(h)
            
        except:
            print("ERRORE LETTURA TEMPERATURA E UMIDITA'")
            return(None,None)
        return(t,h)
    
class PluviometroWH_SP_RG:
    __PLUVIOMETRO = 0.3
    
    def __init__(self,codPluviometro, pluv_pin):
        self.codPluviometro = codPluviometro
        self.__callbackPluviometro = pi.callback(pluv_pin, pigpio.FALLING_EDGE)
        self.__callbackPluviometro.reset_tally()
        
    def LeggiMmPioggia(self):
        scatti = self.__callbackPluviometro.tally()
        self.__callbackPluviometro.reset_tally()
        return (round(scatti*self.__PLUVIOMETRO,1))
    
class Rilevazione:
    def __init__(self, codSensore, data, ora, valore):
        self.IDSensore = codSensore
        self.data = data
        self.ora = ora
        self.valore = valore
        
def LeggiSensori():
    listRilev = []
    
    dataOra = datetime.datetime.now()
    data = dataOra.strftime("%Y/%m/%d")
    ora = dataOra.strftime("%H/%M/%S")
    
    press = barometro.LeggiPressioneBarometrica()
    listRilev.append(Rilevazione(barometro.codBarometro, data, ora, press))
    print("Pressione barometrica hPa = "+str(press))
    
    t,h = dht11.LeggiTemperaturaUmidita()
    listRilev.append(Rilevazione(dht11.codTermometro, data, ora, t))
    listRilev.append(Rilevazione(dht11.codIgrometro, data, ora, h))
    print("Temperatura = "+ str(t)+ "°C")
    print("Umidità relativa = "+str(h) + "%")
    
    
    pluv = pluviometro.LeggiMmPioggia()
    listRilev.append(Rilevazione(pluviometro.codPluviometro,data, ora, pluv))
    print ("Millimetro di pioggia"+str(pluv))
    
    dire = vento.direzione()
    listRilev.append(Rilevazione(vento.codBanderuola,data, ora, dire))
    print("Direzione del vento = "+dire)
    
    velo = vento.media()
    listRilev.append(Rilevazione(vento.codAnemometro,data, ora, velo))
    print("Media intensita del vento Km/h = " +str(velo))
    
    jsonStr = json.dumps([Rilevazione.__dict__ for Rilevazione in listRilev], indent= 3)
    print("Json = ")
    print(jsonStr)
    return(jsonStr)


def InviaDatiSensori():
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)#Istanzia il socket
    s.connect((TCP_IP,TCP_PORT)) #Si connette al server
    Jso = LeggiSensori()
    print ("Json = ")
    print(Jso)
    print("Sending...")
    s.send(Jso.encode()) #Trasmette la stringa Json
    s.close() #Close the socket when done
    
def InviaImmagineCAM():
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM) #Istanzia il socket
    s.connect((TCP_IP, TCP_PORT)) #Si connette al Server
    listRilev = []
    dataOra = datetime.datetime.now()
    data = dataOra.strftime("%Y/%m/%d")
    ora = dataOra.strftime("%H:%M:%S")
    imageByte = camera.LeggiPiCamera()
    listRilev.append(Rilevazione(camera.codCamera, data, ora, str(len(imageByte))))
    jsonStr = json.dumps([Rilevazione.__dict__ for Rilevazione in listRilev], indent=3)
    print("Json = ")
    print(jsonStr)
    print('Sending...')
    s.send(jsonStr.encode())
    s.sendall(imageByte)
    s.close()


print("Start client meteo")

barometro = BarometroBMP180(COD_BAROMETRO)
dht11 = Dht11(COD_TERMOMETRO, COD_IGROMETRO, DHT11_PIN)
vento = VentoAutocostruito(COD_BANDERUOLA, COD_ANEMOMETRO,ANEMO_PIN,DIR0_PIN,DIR1_PIN,DIR2_PIN,DIR3_PIN)
pluviometro = PluviometroWH_SP_RG(COD_PLUVIOMETRO,PLUV_PIN)
#camera= RaspCamera(COD_CAMERA)

TCP_IP = '10.1.0.19'
TCP_PORT =11000

s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

s.connect((TCP_IP, TCP_PORT))
Jso = LeggiSensori()
print('Sending...')
s.send(Jso.encode())
s.close()

PERIODO_DATI = 1
PERIODO_CAM = 2

now = datetime.datetime.now()
newDati = int (now.minute/PERIODO_DATI)
oldDati = newDati
newCam = int(now.minute/PERIODO_CAM)
oldCam = newCam

while True:
    now = datetime.datetime.now()
    
    newDati = int(now.minute / PERIODO_DATI)
    if(newDati != oldDati):
        oldDati = newDati
        print("\nInvio dati meteo")
        print(now)
        InviaDatiSensori()
        print ("Fine invio dati meteo")
        
    newCam = int(now.minute / PERIODO_CAM)
    if (newCam!=oldCam):
        oldCam = newCam
        print("\nInvio immagine CAM")
        print(now)
        InviaImmagineCAM()
        print('Fine invio immagine CAM')
    time.sleep(1)

print("End client meteo")

del vento
del pluviometro

'''s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
s.connect((TCP_IP, TCP_PORT))

listRilev = []
dataOra = datetime.datetime.now()
data = dataOra.strftime("%Y/%m/%d")
ora = dataOra.strftime("%H/%M/%S")
imageByte = camera.LeggiPiCamera()
listRilev.append(Rilevazione(camera.codCamera, data, ora, str(len(imageByte))))
jsonStr = json.dumps([Rilevazione.__dict__ for Rilevazione in listRilev], indent = 3)
s.send(jsonStr.encode())
s.sendall(imageByte)

s.close()
                 
print("End client meteo")'''