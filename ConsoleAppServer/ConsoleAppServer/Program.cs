using System;
using System.Net;
using System.Net.Sockets;
using System.Text;
using System.IO;
using Newtonsoft.Json.Linq;
using Newtonsoft.Json;
using System.Collections.Generic;
using System.Data.SqlClient;
using MySql.Data.MySqlClient;
using System.Security.Principal;

namespace ConsoleAppServer
{
    internal class Program
    {
        internal class DatoSensore //Classe per accogliere i dati
        {
            public int IDSensore { get; set; }

            public string data { get; set; }

            public string ora { get; set; }

            public object valore { get; set; }

            public string dataConOra (){
                return data.Replace("/","-")+ "+"+ora.Replace(":", "-");
            }
        }
        static void Main(string[] args)
        {
            string connectionString = "server = loaclhost; Port = 3306; Database=meteo_db; Uid = root; Pwd= burbero2023";
            MySqlConnection conn;

            Socket listener = new Socket(AddressFamily.InterNetwork, SocketType.Stream, ProtocolType.Tcp);

            IPAddress ipAddress = IPAddress.Parse("10.1.0.19");

            IPEndPoint localEndPoint = new IPEndPoint(ipAddress, 11000);

            try
            {
                listener.Bind(localEndPoint);

            }catch (Exception e)
            {
                Console.WriteLine(e.ToString());
                Console.WriteLine("\n INDIRIZZO IP NON VALIDO");
                Console.WriteLine(" Premi 'invio' per terminare il programma");
                Console.ReadLine();
                return;
            }

            listener.Listen(10);

            while (true)
            {
                Console.WriteLine("\n\nIN ATTESA DI UNA CONNESSIONE...\n");

                Socket handler = listener.Accept();
                string ip = handler.RemoteEndPoint.ToString().Split(':')[0];
                if (ip != "10.1.100.2") //Bisogna controllare che l'ip appaia nell'elenco delle stazioni meteo, quindi con un foreach controllarli tutti
                {
                    handler.Shutdown(SocketShutdown.Both);
                    handler.Close();
                    Console.Write("\n\n" + "CONNESSIONE RIFIUTATA IP = " + ip);
                    continue;
                }

                Console.WriteLine("Connessione accettata da {0}", ip);
                DateTime dataOraDiSistema = DateTime.Now;
                Console.WriteLine($"La data e l'ora di sistema corrente sono: {dataOraDiSistema:dd/MM/yyyy HH:mm:ss}");

                int bytesRec;
                string strJson = "";
                byte[] buffer = new byte[2000000];


                do
                {
                    bytesRec = handler.Receive(buffer, 1, SocketFlags.Partial);
                    strJson += Encoding.ASCII.GetString(buffer, 0, bytesRec);

                } while (!strJson.EndsWith("]"));

                Console.WriteLine("Dati pervenuti:");
                Console.WriteLine(strJson);

                List<DatoSensore> listaSensori = JsonConvert.DeserializeObject<List<DatoSensore>>(strJson);

                try
                {
                    conn = new MySql.Data.MySqlClient.MySqlConnection();
                    conn.ConnectionString = connectionString;
                    conn.Open();
                    Console.WriteLine($"\nMySQL version: {conn.ServerVersion}");

                    MySqlCommand cmd = new MySqlCommand();
                    cmd.Connection = conn;

                    foreach(DatoSensore sensor in listaSensori)
                    {
                        Console.Write("\nArchivia valore sensore ID = " + sensor.IDSensore);
                        string sql = "SELECT COUNT(*) FROM sensoriinstallati WHERE idSensoriInstallati="+sensor.IDSensore.ToString();
                        cmd.CommandText = sql;
                        int ni = Convert.ToInt32(cmd.ExecuteScalar());
                        if(ni == 1) // Validazione: controllo se esiste il sensore installato 
                        {
                            cmd.CommandText = "SELECT COUNT(*) " +
                                              "FROM meteodb.sensori, meteodb.sensoriinstallati " +
                                              "WHERE sensori.idCodiceSensore = sensoriinstallati.idCodiceSensore " +
                                              "AND Camera = TRUE" +
                                              "AND sensoriinstallati.idSensoriInstallati =" + sensor.IDSensore;
                            int nc = Convert.ToInt32(cmd.ExecuteScalar());

                            if(nc == 1)
                            {
                                Console.Write(" è una CAM");
                                sql = "SELECT idNomeStazione " +
                                      "FROM meteodb.sensoriinstallati " +
                                      "WHERE sensoriinstallati.idSensoriInstallati = " + sensor.IDSensore;

                                cmd.CommandText = sql;
                                string codStazione = cmd.ExecuteScalar().ToString();
                                if (!Directory.Exists(codStazione))
                                {
                                    Directory.CreateDirectory(codStazione);
                                }
                                sql = "INSERT INTO rilevamenti(idSensoriInstallati, DataOra, Dato) " +
                                      "VALUES(" + sensor.IDSensore.ToString() + ",'" + sensor.data + " " + sensor.ora + "','" + sensor.valore.ToString() + "');" +
                                      "SELECT LAST_INSERT_ID() rilevamenti;";
                                cmd.CommandText = sql;
                                UInt64 idIdentity = (UInt64)cmd.ExecuteScalar();
                                int numByte = Convert.ToInt32(sensor.valore);
                                byte[] imageBuffer = new byte[numByte];

                                int attBytesRec = 0;
                                do
                                {
                                    attBytesRec = attBytesRec + handler.Receive(imageBuffer, attBytesRec, numByte - attBytesRec, SocketFlags.None);
                                } while (attBytesRec < numByte);

                                string percorso = @"C:\Users\antonio.zangla\Desktop\" + codStazione + @"\" + sensor.dataConOra() + "+" + idIdentity.ToString()+ ".jpg";
                                System.IO.File.WriteAllBytes(percorso, imageBuffer);
                                Console.WriteLine("Percorso file salvato: "+ percorso);
                            }
                            else
                            {
                                sql = "INSERT INTO rilevamente(idSensoriInstallati, DataOra, Dato) " +
                                      "VALUES(" + sensor.IDSensore.ToString() + ",'" + sensor.data + " " + sensor.ora + "','" + sensor.valore + "')";
                                cmd.CommandText = sql;
                                cmd.ExecuteNonQuery();
                            }
                        }
                        else
                        {
                            Console.WriteLine("ERRORE: sensore " + sensor.IDSensore.ToString() + "non installato");
                        }
                        
                    }
                    conn.Close();
                }catch(MySql.Data.MySqlClient.MySqlException ex)
                {
                    Console.WriteLine("\n" + "ERRORE:");
                    Console.WriteLine(ex.Message+"\n");
                }
                handler.Shutdown(SocketShutdown.Both);
                handler.Close();
                Console.Write("\n\n" + "FINE DELLA CONNESSIONE");
            }
        }
    }
}