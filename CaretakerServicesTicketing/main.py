import customtkinter
import smtplib, ssl
import requests
import json
# import os
# import dotenv
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime

# dotenv.load_dotenv()

class AppHome(customtkinter.CTk):
    def __init__(self):
        super().__init__()
        self.width = 900
        self.height = 700
        self.resizable(False, False)
        self.url = "http://127.0.0.1:8000/api/"
        self.update = False

        self.geometry(f"{self.width}x{self.height}")
        self.title("Caretaker Services Ticketing")

        self.login_page()

    def login_page(self):

        self.clear_widgets()

        self.label = customtkinter.CTkLabel(self, text="Caretaker Services Ticketing", fg_color="transparent", width=self.width, justify="center", font=('', 20))
        self.label.grid(row=0, column=0, pady=20)

        self.label_error = customtkinter.CTkLabel(self, text="", fg_color="transparent", width=self.width, justify="center", font=('', 15), text_color="#EE3939")
        self.label_error.grid(row=1, column=0, pady=10)

        self.input_frame = customtkinter.CTkFrame(master=self, width=self.width, height=100)
        self.input_frame.grid(row=2, column=0, pady=10)

        self.entry_email = customtkinter.CTkEntry(self.input_frame, placeholder_text="Email")
        self.entry_email.grid(row=0, column=0, pady=20, padx=20)
        self.entry_pwd = customtkinter.CTkEntry(self.input_frame, placeholder_text="Mot de passe")
        self.entry_pwd.grid(row=0, column=1, pady=20, padx=20)

        self.button_connect = customtkinter.CTkButton(self, command=self.login, text="Se connecter")
        self.button_connect.grid(row=4, column=0, pady=10)

    def login(self):

        email = self.entry_email.get()
        pwd = self.entry_pwd.get()

        if email == '' or pwd == '':
            self.label_error.configure(text="Email ou mot de passe incorrect")
        else:
            headers = {
                'accept': 'application/json',
                'Content-Type': 'application/json'
            }
            data = {
                "username": email,
                "password": pwd
            }

            response = requests.post(self.url + "login", headers=headers, json=data)
            
            self.user = response.json()

            if 'code' in self.user and self.user['code'] == 401:
                self.label_error.configure(text="Email ou mot de passe incorrect")
            elif 'ROLE_ADMIN' not in self.user['user']['roles']:
                self.label_error.configure(text="Email ou mot de passe incorrect")
            else:
                self.token = self.user['token']
                self.show_interface()

    def show_interface(self, success = False, delete = False, update = False):
        self.clear_widgets()

        if success:
            self.display_message("Ticket posté !", "#4CBA78")
        elif delete:
            self.display_message("Ticket supprimé !", "#EE3939")
        elif update:
            self.display_message("Ticket mis à jour !", "#4CBA78")

        self.input_btns = customtkinter.CTkFrame(master=self, width=self.width, height=400)
        self.input_btns.grid(row=2, column=0, pady=10)

        self.create_ticket_btn = customtkinter.CTkButton(self.input_btns, command=self.create_ticket, text="Créer un ticket")
        self.create_ticket_btn.grid(row=1, column=0, pady=20, padx=20)

        self.show_tickets_btn = customtkinter.CTkButton(self.input_btns, command=self.show_tickets, text="Lister les tickets")
        self.show_tickets_btn.grid(row=1, column=1, pady=20, padx=20)

        self.return_btn = customtkinter.CTkButton(self, command=self.login_page, text="Retour")
        self.return_btn.grid(row=3, column=0, pady=10, padx=20)

    def show_tickets(self):
        self.clear_widgets()
        
        headers = {
            "accept": "application/ld+json",
            'Authorization': 'Bearer ' + self.token
        }
        response = requests.get(self.url + "cs_tickets", headers=headers)
        self.tickets = response.json()['hydra:member']

        self.frame_tickets = customtkinter.CTkFrame(master=self, width=self.width, height=500)
        self.frame_tickets.grid(row=1, column=0, pady=20, padx=20)

        for i in range(0, len(self.tickets), 4):
            for j, ticket in enumerate(self.tickets[i:i+4]):
                ticket['shortName'] = ticket['name'][:9] + '..' if len(ticket['name']) > 9 else ticket['name']
                def create_show_details_command(t=ticket):
                    return lambda: self.show_details_ticket(t)
        
                self.post_btn = customtkinter.CTkButton(self.frame_tickets, command=create_show_details_command(), text=f"Ticket #{ticket['id']} : {ticket['shortName']}")
                self.post_btn.grid(row=i//4, column=j, pady=20, padx=20)

        self.return_btn = customtkinter.CTkButton(self, command=self.show_interface, text="Retour", width=140)
        self.return_btn.grid(row=2, column=0, pady=0, padx=20)

    def show_details_ticket(self, ticket):
        self.clear_widgets()

        colors = {'NEW':'#c90076', 'IN PROGRESS':'#e69138', 'FINISH':'#11612F'}
        colors_prio = {'Basse':'#26633a', 'Moyenne':'#f89c2f', 'Haute':'#f83a21'}

        self.infos_frame = customtkinter.CTkFrame(self, width=self.width, height=500)
        self.infos_frame.grid(row=1, column=0, pady=10, padx=20)

        self.label_status = customtkinter.CTkLabel(self.infos_frame, text=ticket['status'], corner_radius=20, fg_color=colors[ticket['status']], width=150, justify="center", font=('', 18))
        self.label_status.grid(row=1, column=0, pady=(20, 10), padx=(20, 0))

        self.label_priority = customtkinter.CTkLabel(self.infos_frame, text=f"Priorité : {ticket['priority']}", corner_radius=20, fg_color=colors_prio[ticket['priority']], width=150, justify="center", font=('', 18))
        self.label_priority.grid(row=1, column=1, pady=(20, 10), padx=(0, 0))

        self.label_subject = customtkinter.CTkLabel(self.infos_frame, text=ticket['subject'], corner_radius=20, fg_color="#1f6aa5", width=150, justify="center", font=('', 18))
        self.label_subject.grid(row=1, column=2, pady=(20, 10), padx=(0, 20))

        self.label_id = customtkinter.CTkLabel(self.infos_frame, text=f"Ticket #{ticket['id']} :", fg_color="transparent", width=200, justify="center", font=('', 18), text_color="#2986CC")
        self.label_id.grid(row=2, column=1, pady=10, padx=20)

        date_closing = datetime.strptime(ticket['dateClosing'], '%Y-%m-%dT%H:%M:%S%z').strftime("%d/%m/%Y") if 'dateClosing' in ticket else None
        date_creation = datetime.strptime(ticket['dateCreation'], '%Y-%m-%dT%H:%M:%S%z').strftime("%d/%m/%Y")
        text = f"Créé le {date_creation}" if 'dateClosing' not in ticket else f"Créé le {date_creation} et fermé le {date_closing}"
        self.label_date = customtkinter.CTkLabel(self.infos_frame, text=text, fg_color="transparent", width=200, justify="center", font=('', 12))
        self.label_date.grid(row=3, column=1, pady=(0, 5), padx=20)

        self.label_name = customtkinter.CTkLabel(self.infos_frame, text=f"Nom : {ticket['name']}", fg_color="transparent", width=200, justify="center", font=('', 16), wraplength=self.width/2)
        self.label_name.grid(row=4, column=1, pady=(10, 5), padx=20)

        self.label_desc = customtkinter.CTkLabel(self.infos_frame, text=f"Description : {ticket['description']}", fg_color="transparent", width=200, justify="center", font=('', 16), wraplength=self.width/2)
        self.label_desc.grid(row=5, column=1, pady=(5, 20), padx=20)

        self.label_author = customtkinter.CTkLabel(self.infos_frame, text=f"Auteur : {ticket['author']['lastname'].upper()} {ticket['author']['firstname'].capitalize()}", fg_color="transparent", width=200, justify="center", font=('', 16), text_color="#2986CC", wraplength=self.width/2)
        self.label_author.grid(row=6, column=1, pady=(5, 20), padx=20)

        if 'clientEmail' in ticket and ticket['clientEmail'] not in [None, '']:
            self.label_client = customtkinter.CTkLabel(self.infos_frame, text=f"Client : {ticket['clientEmail'].lower()}", fg_color="transparent", width=200, justify="center", font=('', 16), wraplength=self.width/2)
            self.label_client.grid(row=7, column=1, pady=(5, 20), padx=20)

        if 'response' in ticket and ticket['response'] is not None:
            self.label_response = customtkinter.CTkLabel(self.infos_frame, text=f"Réponse au ticket : {ticket['response']}", fg_color="transparent", width=200, justify="center", font=('', 16), wraplength=self.width/2, text_color="#11612F")
            self.label_response.grid(row=8, column=1, pady=(5, 20), padx=20)

        self.buttons_frame = customtkinter.CTkFrame(self, width=self.width, height=200)
        self.buttons_frame.grid(row=2, column=0, pady=10, padx=20)

        def update_ticket_command(t=ticket):
            return lambda: self.create_ticket(t)

        self.button_modify = customtkinter.CTkButton(self.buttons_frame, command=update_ticket_command(), text="Modifier", width=140)
        self.button_modify.grid(row=1, column=0, pady=10, padx=20)

        def delete_ticket_command(t=ticket):
            return lambda: self.delete_ticket(t)

        self.button_delete = customtkinter.CTkButton(self.buttons_frame, hover_color="#8E1313", fg_color="#f83a21", command=delete_ticket_command(), text="Supprimer", width=140)
        self.button_delete.grid(row=1, column=1, pady=10, padx=20)

        if ticket['status'] == "NEW":
            def open_ticket_command(t=ticket):
                return lambda: self.open_ticket(t)

            self.button_open = customtkinter.CTkButton(self.buttons_frame, hover_color="#11612F", fg_color="#187E3E", command=open_ticket_command(), text="Ouvrir", width=140)
            self.button_open.grid(row=1, column=2, pady=10, padx=20)
        elif ticket['status'] == "IN PROGRESS":
            def close_ticket_command(t=ticket):
                return lambda: self.close_ticket(t)

            self.button_close = customtkinter.CTkButton(self.buttons_frame, hover_color="#11612F", fg_color="#187E3E", command=close_ticket_command(), text="Fermer", width=140)
            self.button_close.grid(row=1, column=2, pady=10, padx=20)

        self.return_btn = customtkinter.CTkButton(self, command=self.show_tickets, text="Retour", width=140)
        self.return_btn.grid(row=3, column=0, pady=10, padx=20)

    def close_ticket(self, ticket):
        self.clear_widgets()

        self.response_label = customtkinter.CTkLabel(self, text="Réponse au ticket :", width=200)
        self.response_label.grid(row=1, column=0, pady=10, padx=20)

        self.entry_response = customtkinter.CTkTextbox(self, height=70)
        self.entry_response.grid(row=2, column=0, pady=(0, 20), padx=20)

        def close_ticket_command(t=ticket):
            return lambda: self.finish_close_ticket(t)

        self.validate_btn = customtkinter.CTkButton(self, command=close_ticket_command(), hover_color="#11612F", fg_color="#187E3E", text="Valider")
        self.validate_btn.grid(row=3, column=0, pady=10, padx=20)

        def show_details_ticket_command(t=ticket):
            return lambda: self.show_details_ticket(t)

        self.return_btn = customtkinter.CTkButton(self, command=show_details_ticket_command(), text="Retour", width=140)
        self.return_btn.grid(row=4, column=0, pady=10, padx=20)

    def finish_close_ticket(self, ticket):

        ticket_response = self.entry_response.get("1.0", customtkinter.END).strip()

        headers = {
            "accept": "application/ld+json",
            "Authorization": "Bearer " + self.token,
            "Content-Type": "application/merge-patch+json"
        }
        data = {
            "status": "FINISH",
            "response": ticket_response,
            "dateClosing": datetime.now().isoformat()
        }

        response = requests.patch(f"{self.url}cs_tickets/{ticket['id']}", headers=headers, json=data)

        if 'clientEmail' in ticket and ticket['clientEmail'] not in [None, '']:

            smtp_address = "smtp.ionos.fr"
            email        = "ne-pas-repondre@caretakerservices.fr"
            # password     = os.environ.get('EMAIL_PASSWORD')
            password     = "1Connard!"

            smtp_port    = 465

            message = MIMEMultipart("alternative")
            message['From'] = email
            message['To'] = ticket['clientEmail']
            message['Subject'] = f"Réponse à votre ticket : {ticket['name']}"

            html = f"""\
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Réponse à votre ticket</title>
            </head>
            <body>
                <p>Bonjour,<br><br>
                Un administrateur de Caretaker Services a répondu à votre ticket contenant cette demande : {ticket['description']}<br><br>
                Voici la réponse : {ticket_response}<br><br>
                Cordialement,<br><br>
                <b>L'équipe Caretaker Services</b><br>
                <a href="http://www.caretakerservices.fr/">www.caretakerservices.fr</a><br><br>
                <a href="http://www.caretakerservices.fr/"><img src="https://drive.google.com/uc?id=1eGWDUCpusvWriomTN0qY9Hjm_KlFcRax" alt="Logo" width="280" style="display: block;"></a><br><br>
                <i>Ce mail a été généré automatiquement, merci de ne pas y répondre.</i>
                </p>
            </body>
            </html>
            """
            message.attach(MIMEText(html, "html"))

            context = ssl.create_default_context()
            try:
                with smtplib.SMTP_SSL(smtp_address, smtp_port, context=context) as server:
                    server.login(email, password)
                    server.sendmail(email, ticket['clientEmail'], message.as_string())
                    print("Email envoyé avec succès!")
            except smtplib.SMTPDataError as e:
                print(f"Erreur SMTP: {e}")
            except Exception as e:
                print(f"Une erreur est survenue: {e}")

        if response.status_code == 200:
            self.show_details_ticket(response.json())

    def delete_ticket(self, ticket):
        headers = {
            'accept': '*/*',
            'Authorization': 'Bearer ' + self.token,
            "Content-Type": "application/json"
        }
        response = requests.delete(f"{self.url}cs_tickets/{ticket['id']}", headers=headers)
        
        if response.status_code == 204:
            self.show_interface(delete=True)
        else:
            print(f"Error: {response.status_code}, {response.text}")

    def open_ticket(self, ticket):
        headers = {
            "accept": "application/ld+json",
            "Authorization": "Bearer " + self.token,
            "Content-Type": "application/merge-patch+json"
        }
        data = {
            "status": "IN PROGRESS"
        }

        response = requests.patch(f"{self.url}cs_tickets/{ticket['id']}", headers=headers, json=data)

        if response.status_code == 200:
            self.show_details_ticket(response.json())

    def create_ticket(self, ticket = None):
        self.clear_widgets()

        self.label_create_error = customtkinter.CTkLabel(self, text="", fg_color="transparent", width=200, justify="center", font=('', 15), text_color="#EE3939")
        self.label_create_error.grid(row=1, column=0, pady=(0, 5), padx=20)

        self.frame = customtkinter.CTkFrame(master=self, width=self.width, height=500)
        self.frame.grid(row=2, column=0, pady=10)

        self.label_subject = customtkinter.CTkLabel(self.frame, text="Sujet", fg_color="transparent", width=200, justify="center", font=('', 15))
        self.label_subject.grid(row=1, column=0, pady=(10, 5), padx=20)

        self.option_menu_sub = customtkinter.CTkOptionMenu(self.frame, values=['Locations', 'Prestations', 'Site Web'])
        self.option_menu_sub.grid(row=2, column=0, pady=(0, 20), padx=20)

        self.label_priority = customtkinter.CTkLabel(self.frame, text="Priorité", fg_color="transparent", width=200, justify="center", font=('', 15))
        self.label_priority.grid(row=3, column=0, pady=(5, 5), padx=20)

        self.option_menu_prio = customtkinter.CTkOptionMenu(self.frame, values=['Basse', 'Moyenne', 'Haute'])
        self.option_menu_prio.grid(row=4, column=0, pady=(0, 20), padx=20)

        self.entry_name = customtkinter.CTkEntry(self.frame, placeholder_text="Titre", width=200)
        self.entry_name.grid(row=5, column=0, pady=20, padx=20)

        self.label_desc = customtkinter.CTkLabel(self.frame, text="Description", fg_color="transparent", width=200, justify="center", font=('', 15))
        self.label_desc.grid(row=6, column=0, pady=(10, 5), padx=20)

        self.entry_description = customtkinter.CTkTextbox(self.frame, height=70)
        self.entry_description.grid(row=7, column=0, pady=(0, 20), padx=20)

        self.entry_client = customtkinter.CTkEntry(self.frame, placeholder_text="Email du client (optionnel)", width=200)
        self.entry_client.grid(row=8, column=0, pady=20, padx=20)

        self.post_btn = customtkinter.CTkButton(self, command=self.post_ticket, text="Poster")
        self.post_btn.grid(row=4, column=0, pady=20, padx=20)

        self.return_btn = customtkinter.CTkButton(self, command=self.show_interface, text="Retour")
        self.return_btn.grid(row=5, column=0, pady=0, padx=20)

        if ticket is not None:

            def show_detail_btn_command(t=ticket):
                return lambda: self.show_details_ticket(t)

            self.update = True
            self.updateTicketId = ticket['id']
            self.entry_name.insert(0, ticket['name'])
            self.entry_description.insert("1.0", ticket['description'])
            if 'clientEmail' in ticket and ticket['clientEmail'] not in [None, '']:
                self.entry_client.insert(0, ticket['clientEmail'])
            self.post_btn.configure(text="Modifier")
            self.option_menu_sub.set(ticket['subject'])
            self.option_menu_prio.set(ticket['priority'])
            self.return_btn.configure(command=show_detail_btn_command())

    def post_ticket(self):

        name = self.entry_name.get()
        description = self.entry_description.get("1.0", customtkinter.END).strip()
        clientEmail = self.entry_client.get()

        if name == "" or description == "":
            self.label_create_error.configure(text="Le nom et la description sont obligatoires")
        else:
            if self.update:
                headers = {
                    "accept": "application/ld+json",
                    "Authorization": "Bearer " + self.token,
                    "Content-Type": "application/merge-patch+json"
                }
                data = {
                    "author": f"api/cs_users/{self.user['user']['id']}",
                    "name": name,
                    "description": description,
                    "subject": self.option_menu_sub.get(),
                    "priority": self.option_menu_prio.get()
                }

                if clientEmail != "":
                    data['clientEmail'] = clientEmail

                response = requests.patch(f"{self.url}cs_tickets/{self.updateTicketId}", headers=headers, json=data)
            else:
                headers = {
                    'accept': 'application/ld+json',
                    'Content-Type': 'application/ld+json',
                    'Authorization': 'Bearer ' + self.token
                }
                data = {
                    "author": f"api/cs_users/{self.user['user']['id']}",
                    "name": name,
                    "description": description,
                    "subject": self.option_menu_sub.get(),
                    "priority": self.option_menu_prio.get()
                }

                if clientEmail != "":
                    data['clientEmail'] = clientEmail

                response = requests.post(self.url + "cs_tickets", headers=headers, json=data)

            if 'author' in response.json():
                if self.update:
                    self.show_interface(update=True)
                    self.update = False
                else:
                    self.show_interface(success=True)
    
    def display_message(self, message, color):
        self.label_error = customtkinter.CTkLabel(self, text=message, fg_color="transparent", width=self.width, justify="center", font=('', 15), text_color=color)
        self.label_error.grid(row=1, column=0, pady=10)

    def clear_widgets(self):
        for widget in self.winfo_children():
            widget.destroy()

        self.label = customtkinter.CTkLabel(self, text="Caretaker Services Ticketing", fg_color="transparent", width=self.width, justify="center", font=('', 20))
        self.label.grid(row=0, column=0, pady=20)

app = AppHome()
app.mainloop()