//---------------------------------------------------------------------------
#ifndef mainH
#define mainH
//---------------------------------------------------------------------------
#include <vcl\Classes.hpp>
#include <vcl\Controls.hpp>
#include <vcl\StdCtrls.hpp>
#include <vcl\Forms.hpp>
#include <vcl\ExtCtrls.hpp>
#include <vcl\Dialogs.hpp>
#include <vcl\Buttons.hpp>
#include <vcl\FileCtrl.hpp>
#include <vcl\Menus.hpp>
//---------------------------------------------------------------------------
class Tampcfgform : public TForm
{
__published:	// IDE-managed Components
	TImage *Image1;
	TGroupBox *GroupBox1;
	TGroupBox *GroupBox2;
	TLabel *Label1;
	TLabel *Label2;
	TEdit *privatetree;
	TEdit *publictree;
	TLabel *Label3;
	TEdit *hostname;
	TEdit *ampurl;
	TEdit *rooturl;
	TEdit *adminurl;
	TEdit *cgiurl;
	TLabel *Label4;
	TLabel *Label5;
	TLabel *Label6;
	TLabel *Label7;
	TButton *Button1;
	TMainMenu *MainMenu1;
	TMenuItem *File1;
	TMenuItem *Exit1;
	TMenuItem *Help1;
	TMenuItem *About1;
	TMenuItem *Applychanges1;
	TEdit *sitestree;
	TLabel *Label8;
	TCheckBox *override;
	TImage *Image2;
	TBevel *Bevel1;
	void __fastcall Exit1Click(TObject *Sender);
	void __fastcall About1Click(TObject *Sender);
	void __fastcall hostnameChange(TObject *Sender);
	
	
	void __fastcall Button1Click(TObject *Sender);
	void __fastcall FormCreate(TObject *Sender);
	void __fastcall Applychanges1Click(TObject *Sender);
	void __fastcall overrideClick(TObject *Sender);
private:	// User declarations
public:		// User declarations
	__fastcall Tampcfgform(TComponent* Owner);
};
//---------------------------------------------------------------------------
extern Tampcfgform *ampcfgform;
bool makecfgpathfile( char *filename );
char *strtr( char *string, char *from, char *to );
//---------------------------------------------------------------------------
#endif
